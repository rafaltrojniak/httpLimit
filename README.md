== Memcache usage ==
- ~100 bytes per entry in memory
- Maximum floor(lifetime/resolution) entries per request - saved for lifetime+draft
- Minimum floor(lifetime/resolution) commands per request, maximum 2\*floor(lifetime/resolution)

=== Examples of memcache stats ===
Creating entries : ~20req/s with random urls

lifetime:30 resolution:5
STAT bytes 440704
STAT curr\_items 5008


lifetime:60 resolution:5
STAT bytes 1356784
STAT curr\_items 15418


