<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Support\Plan as Entitlement;
use App\Support\Stripe;
use App\Support\StripeSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Creating and editing the plans people can be on.
 *
 * The rules that are not obvious from the form:
 *
 *  - `free` cannot be deleted or deactivated. It is what everyone is on before
 *    they pay and what they fall back to when a subscription lapses; without it
 *    Entitlement::for() has nothing to return.
 *  - A plan with subscribers is deactivated rather than deleted. Deleting it
 *    would leave live subscriptions pointing at a price the app no longer
 *    recognises while Stripe carries on charging for it.
 *  - Both price ids are editable, because Stripe's test and live worlds do not
 *    share them. Editing the one for the mode you are not in is normal and the
 *    form says which is which.
 */
class PlanController extends Controller
{
    /** The generation kinds a plan can allow. */
    private const KINDS = ['story', 'narration', 'rewind'];

    public function index(Request $request): View
    {
        return view('admin.plans.index', [
            'plans'  => Plan::orderBy('position')->orderBy('id')->get(),
            'mode'   => Stripe::mode(),
            'kinds'  => self::KINDS,
            'counts' => $this->subscriberCounts(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.plans.edit', [
            'plan'  => new Plan(['quotas' => array_fill_keys(self::KINDS, 0), 'is_active' => true]),
            'kinds' => self::KINDS,
            'mode'  => Stripe::mode(),
        ]);
    }

    public function edit(Request $request, Plan $plan): View
    {
        return view('admin.plans.edit', [
            'plan'  => $plan,
            'kinds' => self::KINDS,
            'mode'  => Stripe::mode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plan = Plan::create($this->validated($request));

        return $this->saved($plan, "“{$plan->label}” created.");
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validated($request, $plan);

        // The free plan's key is referenced in code as a constant, and every
        // fallback path returns it. Renaming it would strand those.
        if ($plan->isFree()) {
            $data['key'] = Plan::FREE;
            $data['is_active'] = true;
        }

        $plan->update($data);

        return $this->saved($plan, "“{$plan->label}” saved.");
    }

    /**
     * Save locally first, then try Stripe.
     *
     * That order matters. If Stripe is unreachable or the key is wrong, the
     * administrator's typing is already safe here and the message says what did
     * not happen over there — rather than losing the edit to someone else's
     * outage. Saving again once the key is fixed picks up where it left off.
     */
    private function saved(Plan $plan, string $message): RedirectResponse
    {
        Entitlement::flush();

        try {
            if ($note = StripeSync::plan($plan->fresh())) {
                $message .= ' '.$note;
            }
        } catch (\RuntimeException $e) {
            Entitlement::flush();

            return redirect()->route('admin.plans')
                ->with('status', $message)
                ->withErrors(['stripe' => $e->getMessage()]);
        }

        Entitlement::flush();

        return redirect()->route('admin.plans')->with('status', $message);
    }

    public function destroy(Request $request, Plan $plan): RedirectResponse
    {
        if ($plan->isFree()) {
            return back()->withErrors([
                'plan' => 'The free plan cannot be deleted — it is what everyone is on before they pay, and what a lapsed subscription falls back to.',
            ]);
        }

        $subscribers = $this->subscriberCounts()[$plan->key] ?? 0;

        if ($subscribers > 0) {
            return back()->withErrors([
                'plan' => "{$subscribers} ".($subscribers === 1 ? 'person is' : 'people are')
                    .' on this plan. Deleting it would leave them pointing at a price this app no longer'
                    .' recognises while Stripe carries on charging them. Deactivate it instead — it stays'
                    .' working for them and disappears from the picker.',
            ]);
        }

        $label = $plan->label;
        $plan->delete();

        Entitlement::flush();

        return redirect()->route('admin.plans')->with('status', "“{$label}” deleted.");
    }

    /* ── helpers ─────────────────────────────────────────────────────────── */

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'key' => [
                'required', 'string', 'max:40', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique('plans', 'key')->ignore($plan?->id),
            ],
            'label'    => ['required', 'string', 'max:60'],
            'blurb'    => ['nullable', 'string', 'max:200'],
            'display'  => ['nullable', 'string', 'max:60'],
            'interval' => ['nullable', 'string', 'in:month,year'],
            // Typed as major units on the form — 12.00 — and stored as minor
            // units, because Stripe counts in cents and money in a float is
            // how rounding errors reach an invoice.
            'amount_major' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'stripe_price'      => ['nullable', 'string', 'max:120', 'starts_with:price_'],
            'stripe_price_test' => ['nullable', 'string', 'max:120', 'starts_with:price_'],
            'position'  => ['nullable', 'integer', 'min:0', 'max:999'],
            'quotas'    => ['nullable', 'array'],
            'quotas.*'  => ['nullable', 'integer', 'min:0', 'max:1000'],
        ], [
            'key.regex' => 'The key is used in code and in the database, so it must be lowercase letters, numbers, dashes or underscores.',
            'stripe_price.starts_with' => 'A Stripe price id starts with price_. A product id (prod_…) will not work.',
            'stripe_price_test.starts_with' => 'A Stripe price id starts with price_. A product id (prod_…) will not work.',
        ]);

        $data['amount'] = filled($data['amount_major'] ?? null)
            ? (int) round(((float) $data['amount_major']) * 100)
            : null;
        $data['currency'] = filled($data['currency'] ?? null)
            ? strtolower($data['currency'])
            : config('escalate.billing.currency', 'usd');
        unset($data['amount_major']);

        $data['is_active'] = $request->boolean('is_active');
        $data['position'] = (int) ($data['position'] ?? 0);
        $data['quotas'] = collect(self::KINDS)
            ->mapWithKeys(fn ($kind) => [$kind => (int) ($data['quotas'][$kind] ?? 0)])
            ->all();

        return $data;
    }

    /**
     * How many people each plan actually holds.
     *
     * Counts manual overrides as well as live subscriptions, because a comped
     * account is just as stranded by a deleted plan as a paying one.
     */
    private function subscriberCounts(): array
    {
        $byOverride = User::whereNotNull('plan_override')
            ->selectRaw('plan_override, count(*) as total')
            ->groupBy('plan_override')
            ->pluck('total', 'plan_override')
            ->all();

        $counts = $byOverride;

        foreach (Plan::all() as $plan) {
            foreach ([$plan->stripe_price, $plan->stripe_price_test] as $price) {
                if (blank($price)) {
                    continue;
                }

                $live = \Laravel\Cashier\Subscription::where('stripe_price', $price)
                    ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
                    ->count();

                $counts[$plan->key] = ($counts[$plan->key] ?? 0) + $live;
            }
        }

        return $counts;
    }
}
