#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$ROOT_DIR/.stream_manager.pid"

if [[ ! -f "$PID_FILE" ]]; then
  echo "No PID file found. Nothing to stop."
  exit 0
fi

PID="$(cat "$PID_FILE")"
if kill -0 "$PID" 2>/dev/null; then
  kill "$PID"
  sleep 1
  if kill -0 "$PID" 2>/dev/null; then
    kill -9 "$PID"
  fi
  echo "Stopped stream manager PID=$PID"
else
  echo "Process $PID is not running."
fi

rm -f "$PID_FILE"
