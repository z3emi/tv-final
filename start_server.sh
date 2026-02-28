#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$ROOT_DIR/.stream_manager.pid"
LOG_FILE="$ROOT_DIR/logs/stream_manager.log"

mkdir -p "$ROOT_DIR/logs" "$ROOT_DIR/live/commands"

if [[ -f "$PID_FILE" ]] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
  echo "Stream manager is already running with PID $(cat "$PID_FILE")"
  exit 0
fi

TV_LIVE_ROOT="${TV_LIVE_ROOT:-$ROOT_DIR/live}" \
TV_CHANNELS_FILE="${TV_CHANNELS_FILE:-$ROOT_DIR/channels.txt}" \
nohup python3 "$ROOT_DIR/stream_with_latency.py" >> "$LOG_FILE" 2>&1 &

echo $! > "$PID_FILE"
echo "Stream manager started. PID=$(cat "$PID_FILE")"
