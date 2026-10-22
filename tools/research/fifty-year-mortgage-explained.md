# Research notes: the 50-year mortgage

Article: `/blog/fifty-year-mortgage-explained` (short link `/50-year`).
Podcast script: `tools/podcast/fifty-year-mortgage-explained.txt`.
Researched 22 October 2026. Every fact in the article and the script is listed here with its
source and the words in it. Dated items are starred (★).

The spine is her video `trump-50-year-mortgage` (`ceo_erika/intake/transcripts/video/`),
recorded when the idea was announced in November 2025. **A recording's date is not today's
date**: the proposal was re-checked first. As of 22 Oct 2026 it is not a product anyone can
get: FHFA's director said in January 2026 the administration had "other priorities", and the
rules that govern almost every mortgage (Regulation Z's Qualified Mortgage definition, Fannie
Mae's Selling Guide) still cap the term at 30 years. The article is framed that way: her
trade-offs, which still hold, and where the idea stands.

## Sources

| # | Source | Type | URL | Read |
|---|---|---|---|---|
| E1 | Erika, video `trump-50-year-mortgage` (November 2025) | her own words | — | transcript |
| 1 | NPR, *3 questions about Trump's 50-year mortgage plan* (12 Nov 2025) ★ | national news | https://www.npr.org/2025/11/12/nx-s1-5604384/50-year-mortgage-trump-housing-explainer | curl as a browser, 22 Oct, 200 |
| 2 | Politico (Cassandra Dumay, Irie Sentner), *Pulte backs away from 50-year mortgage*, as republished by NASCUS (15 Jan 2026) ★ | national news, via the state financial regulators' association | https://www.nascus.org/2026/01/15/pulte-backs-away-from-50-year-mortgage/ | curl as a browser, 22 Oct, 200 |
| 3 | 12 CFR 1026.43, Regulation Z, *Minimum standards for transactions secured by a dwelling* (eCFR, current) ★ | federal regulation | https://www.ecfr.gov/current/title-12/section-1026.43 | eCFR API, 22 Oct, 200 |
| 4 | Fannie Mae Selling Guide, B2-1.5-02, *Loan Eligibility* (06/05/2024; Guide published 7 Oct 2026) ★ | official, GSE guide | https://selling-guide.fanniemae.com/sel/b2-1.5-02/loan-eligibility | curl as a browser, 22 Oct, 200 |
| — | Payment arithmetic: standard amortization formula, $300,000 at 7.00% for 360 and 600 months, principal and interest only | our own illustration | — | computed 22 Oct |

## Claims → sources

| Claim as used | Src | Words in the source |
|---|---|---|
| Hook: carrying your mortgage into your eighties; cut your monthly payment | E1 | "Imagine carrying your mortgage into your eighties. Sounds extreme? But what if you could cut your monthly payment right now?" |
| Half a century of payments | E1 | "Yes, five-zero. Half a century of payments." |
| Why it came up: high prices and rates; 30-year not cutting it for first-timers | E1 | "because the high home prices and high interest rates, the traditional thirty-year mortgage just isn't cutting it for a lot of buyers, especially first-timers and younger families" |
| Her trade-off | E1 | "Lower monthly payment, yes, but more total interest, absolutely, and slower equity buildup, which means it could take longer to actually own anything if that's important to you." |
| Longer commitments, more debt, house poor | E1 | "it also means longer commitments, more debt, and the risk of being house poor for longer" |
| Sellers and investors: demand; watch inflation | E1 | "for sellers and investors, this might increase demand and more people may qualify. But we gotta watch for market inflation and delayed resale cycles." |
| Real cost of money; a wealth strategy | E1 | "It's about understanding the real cost of money over time. Your mortgage is more than a payment. It's a wealth strategy if done correctly." |
| Running the numbers | E1 | "Would you be interested in running the numbers or exploring smarter options? Well, I'm just a DM away." |
| Announced November 2025; Pulte "a complete game changer" ★ | 1 | "President Trump wants the federal government to back a 50-year mortgage option for homebuyers — a plan that would be 'a complete game changer,' according to Federal Housing Finance Agency Director Bill Pulte." (12 Nov 2025: "revealed over the weekend") |
| Realtor.com example: about $250 a month saved; 86% more interest; same rate assumed; real rate would be higher ★ | 1 | "a 50-year loan would save at most about $250 per month compared to the 30-year loan" / "That's 86% more interest over the life of the loans." / "his hypothetical example assumes the same interest rate for both loans. In reality, he says, the interest rate for a 50-year loan would be higher than one for 30 years" |
| Mostly interest for the first 10 years | 1 | "You're going to be paying almost all interest for the first 10 years." (Chris Hendrix, NBKC Bank) |
| January 2026: "I think we have other priorities" ★ | 2 | "'I think we have other priorities,' Pulte told reporters, when asked if 50-year mortgages are still on the table." |
| Qualified Mortgage: term not over 30 years ★ | 3 | §1026.43(e)(2): a qualified mortgage is a covered transaction "(ii) For which the loan term does not exceed 30 years" |
| Fannie Mae buys loans with terms up to 30 years ★ | 4 | "Fannie Mae purchases or securitizes loans that have original terms up to 30 years." / ATR Covered Loans must "have a loan term not exceeding 30 years" |
| Illustration: $300,000 at 7%: 30-year $1,996/mo, ~$418,500 interest, ~$42,600 principal repaid in 10 years; 50-year $1,805/mo, ~$783,000 interest, ~$9,500 principal in 10 years; ~$191/mo less, ~$364,500 more interest | — | computed; same rate assumed for both (source 1 says a real 50-year rate would likely be higher) |

## Deliberately left out

- **Her "European Commission eased its restrictions on AI" line:** unrelated to mortgages, and
  unchecked.
- **"US regulators are considering easing mortgage rules"** as a present-tense fact: true of
  November 2025, not of today (source 2). The article says what changed.
- **Today's average rate** (Freddie Mac PMMS 7.32% on 22 Oct 2026): it goes stale before the
  episode airs. The illustration uses a round 7% and says so.
- **Escaluxe's offer in her close** ("At Escaluxe, we don't just find houses…"): the article's
  call to action goes to Erika at Axen Realty, as every article's does.
- **Freddie Mac's and FHA's own term limits:** not read today; Regulation Z and Fannie Mae carry
  the point.
