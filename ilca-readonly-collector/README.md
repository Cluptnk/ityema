# ILCA read-only collector

This is a clean-room, monitoring-only replacement for the polling part of `APP2.exe`. It sends only the legacy monitoring request (`151 / 2`) found in the supplied binary. It never writes MySQL data, reads the `task` table, uploads files, downloads files, stops a loom, or starts a loom.

## Configure a loom

Copy the actual loom IP address and TCP port from a known-safe ILCA configuration. Do not use an address until it has been approved for read-only monitoring.

```json
{
  "looms": [
    {
      "name": "Loom-01",
      "host": "192.168.2.50",
      "port": 12888,
      "eventCount": 20
    }
  ]
}
```

## Run one safe polling cycle

```powershell
dotnet run -- --config appsettings.json --once
```

Every response is saved as a binary capture under `data/captures`; `data/polls.jsonl` contains timestamps, byte counts, SHA-256 hashes, and errors. These captures are the input for the next phase: a protocol decoder that can be tested without touching a live loom.
