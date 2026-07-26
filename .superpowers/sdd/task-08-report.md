# Task 8 Report — Build pipeline with terser + source maps

**Status:** ✅ Complete

## Changes
- **`phantom-core/build.js`**: Replaced simple copy with terser minification + source map generation. File order corrected to services → adapters → renderers → entry point.
- **`phantom-core/frontend/assets/js/services/auth-service.js`**: Fixed syntax error — missing `;` after `Auth` object literal closure causing terser parse failure.
- **`phantom-core/frontend/assets/js/phantom-data.js`**: Regenerated with new file order.
- **`phantom-core/frontend/assets/js/phantom-core.min.js`**: New — minified bundle with source map comment.
- **`phantom-core/frontend/assets/js/phantom-core.min.js.map`**: New — source map.

## Output sizes
| File | Before | After | Reduction |
|------|--------|-------|-----------|
| `phantom-data.js` | 26.2 KB | 26.2 KB | 0% (unminified) |
| `phantom-core.min.js` | — | **16.7 KB** (17,150 B) | **36.1%** vs unminified |
| `phantom-core.min.js.map` | — | 18.4 KB (18,857 B) | new |

## Verification
- `node build.js` succeeds with all 3 expected output lines
- `node --check` passes on all source files
- `//# sourceMappingURL=phantom-core.min.js.map` present in minified output
- terser 5.49.0 already installed

## Commit
```
969874f feat(phase0): upgrade build.js with terser minification + source maps
```
