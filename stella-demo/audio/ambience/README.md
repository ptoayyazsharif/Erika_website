# Ambience beds

Drop three looping mp3s in this folder, named exactly:

```
cafe.mp3
rain.mp3
waves.mp3
```

Two to five minutes each, seamless loop, mixed quiet — they play at 25% gain
under the voice. Pixabay and Freesound both have royalty-free options; check
the licence before you use one commercially.

**You do not have to.** If a file is missing, the player synthesises that bed
in the browser from filtered noise (see `buildSynth()` in `js/player.js`).
It sounds convincing under narration, costs nothing, and means the demo is
never waiting on assets. Real recordings are still better — swap them in when
you have them and the player picks them up automatically on the next load.
