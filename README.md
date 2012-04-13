## Memcache usage

- ~100 bytes per entry in memory
- Maximum floor(lifetime/resolution) entries per request - saved for lifetime+draft
- Minimum floor(lifetime/resolution) commands per request, maximum 2\*floor(lifetime/resolution)

## Examples of memcache stats

Creating entries : ~20req/s with random urls

lifetime:30 resolution:5
STAT bytes 440704
STAT curr\_items 5008


lifetime:60 resolution:5
STAT bytes 1356784
STAT curr\_items 15418

## Configuration

- prefix - prefix of the keys in memcached
- server - array for memcache server (host and port keys)
- keys - array of keys from the $\_SERVER array to use in the key
- limits - array of arrays.
	- first level key - field name to use when computing limit ( must be used in keys )
	- second level key - value of field from $\_SERVER
	- second level value - limit to set (see example)
- lifetime - Life of the key - timespan of the check
- resolution - How precisely we should check the lifetime
- quietMemcacheFail - in case of memcache problems, just return, no exception should be thrown
- returnCode - Return code to use when blocking
- closeConnection - true if you want to send "Connection: Close" on blocking
