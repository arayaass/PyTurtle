# Original art and replacement guide

All character, forest, prop, animal, and effect artwork in this project was drawn specifically for PyTurtle in SVG or Canvas code. No Harvest Moon assets or other game sprites are included.

- `characters/shiha/portrait.svg`: original explorer portrait, used in story and small screens.
- `nature/forest-daylight.svg`: original daylight forest scene used by authentication, Home, and chapter cards.
- `resources/js/game/Game.js`: procedural character with idle and four walking directions; ground, trees, paths, river, bridge, bushes, flowers, rocks, logs, sign, ruins, checkpoint flags, treasure chest, butterfly, bird, rabbit, squirrel, fish, leaf particles.
- `resources/js/game/systems/audio.js`: synthesized quiet ambient tones and checkpoint sounds. No downloaded music.

These vector/procedural assets are functional original placeholders, designed to be replaced without changing gameplay. Replace the SVGs at the same paths. For sprite sheets, replace `ForestGame.shiha()` with a sprite renderer driven by `player.x`, `player.y`, `player.angle`, and `moving`. Preserve the logical canvas dimensions (600 × 460), feet as the position anchor, and collisions/checkpoints in the shared level data. Store directional animation files under `characters/shiha/`.

Optional replacement folders: `tiles/`, `animals/`, `effects/`, `audio/`, `ui/`. Use assets with compatible licenses and include attribution if required. Keep reduced-motion and audio settings working.
