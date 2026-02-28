import os
import subprocess
import threading
import time
import shutil
import json
from datetime import datetime
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from rich.console import Console
import psutil

SCRIPT_DIR = os.path.dirname(os.path.realpath(__file__))
DEFAULT_LIVE_ROOT = os.environ.get("TV_LIVE_ROOT", os.path.join(SCRIPT_DIR, "live"))
DEFAULT_FFMPEG = os.environ.get("TV_FFMPEG_PATH") or shutil.which("ffmpeg") or "/usr/bin/ffmpeg"

# --- الإعدادات النهائية والمستقرة ---
CONFIG = {
    "channels_file": os.environ.get("TV_CHANNELS_FILE", os.path.join(SCRIPT_DIR, "channels.txt")),
    "output_root": DEFAULT_LIVE_ROOT,
    "ffmpeg_path": DEFAULT_FFMPEG,
    "http_port": int(os.environ.get("TV_HLS_HTTP_PORT", "8000")),
    "check_interval": 3,
    "status_json_file": "status.json",
    "commands_dir": os.path.join(DEFAULT_LIVE_ROOT, "commands"),
    "restart_threshold": 5,
    "cooldown_time": 60,
}

USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36"
FFMPEG_PROCESSES = {}
CHANNEL_START_TIMES = {}
RESTART_COUNTS = {}
STALLED_CHANNELS = set()
COOLDOWN_CHANNELS = {}
console = Console()


class CORSHandler(SimpleHTTPRequestHandler):
    def __init__(self, *args, directory=None, **kwargs):
        if directory is None:
            directory = os.getcwd()
        super().__init__(*args, directory=directory, **kwargs)

    def end_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Cache-Control', 'no-store, must-revalidate')
        super().end_headers()

    def log_message(self, format, *args):
        pass


def run_http_server():
    handler = lambda *args, **kwargs: CORSHandler(*args, directory=CONFIG["output_root"], **kwargs)
    server = ThreadingHTTPServer(('0.0.0.0', CONFIG['http_port']), handler)
    console.print(f"🚀 Fast HLS Server running on http://0.0.0.0:{CONFIG['http_port']}", style="bold green")
    server.serve_forever()


def start_ffmpeg_process(channel_id, input_url):
    if channel_id in COOLDOWN_CHANNELS and time.time() < COOLDOWN_CHANNELS[channel_id]:
        return

    output_dir = os.path.join(CONFIG["output_root"], f"channel_{channel_id}")
    if os.path.exists(output_dir):
        shutil.rmtree(output_dir, ignore_errors=True)
    os.makedirs(output_dir, exist_ok=True)

    command = [
        CONFIG["ffmpeg_path"], "-hide_banner", "-nostdin", "-loglevel", "warning",
        "-rw_timeout", "20000000", "-thread_queue_size", "1024",
        "-fflags", "+igndts+genpts+discardcorrupt",
        "-reconnect", "1", "-reconnect_streamed", "1", "-reconnect_delay_max", "5", "-reconnect_on_network_error", "1",
        "-user_agent", USER_AGENT, "-i", input_url,
        "-map", "0:v:0", "-map", "0:a:0?",
        "-c:v", "libx264", "-preset", "veryfast", "-tune", "zerolatency", "-crf", "23",
        "-maxrate", "3000k", "-bufsize", "6000k", "-g", "48", "-keyint_min", "48",
        "-c:a", "aac", "-b:a", "128k", "-ar", "44100",
        "-f", "hls", "-hls_time", "4", "-hls_list_size", "12",
        "-hls_flags", "delete_segments+append_list+independent_segments+omit_endlist",
        os.path.join(output_dir, "stream.m3u8")
    ]

    try:
        log_file = open(os.path.join(output_dir, "ffmpeg.log"), 'w', encoding='utf-8')
        proc = subprocess.Popen(command, stderr=log_file, stdout=subprocess.DEVNULL)
        FFMPEG_PROCESSES[channel_id] = {'proc': proc, 'url': input_url, 'log_file': log_file, 'start_time': time.time()}
        CHANNEL_START_TIMES[channel_id] = datetime.now()
        console.print(f"✅ Channel {channel_id} started (PID: {proc.pid})", style="green")
    except Exception as e:
        console.print(f"❌ Failed to start channel {channel_id}: {e}", style="bold red")


def stop_ffmpeg_process(channel_id):
    if channel_id in FFMPEG_PROCESSES:
        proc_info = FFMPEG_PROCESSES[channel_id]
        try:
            p = psutil.Process(proc_info['proc'].pid)
            for child in p.children(recursive=True):
                child.kill()
            p.kill()
        except psutil.NoSuchProcess:
            pass
        if 'log_file' in proc_info and not proc_info['log_file'].closed:
            proc_info['log_file'].close()
        del FFMPEG_PROCESSES[channel_id]
    CHANNEL_START_TIMES.pop(channel_id, None)
    STALLED_CHANNELS.discard(channel_id)


def get_all_channels_from_file():
    all_channels = {}
    channels_path = CONFIG["channels_file"]
    try:
        with open(channels_path, 'r', encoding='utf-8') as f:
            for line in f:
                if '|' in line:
                    is_enabled = not line.strip().startswith('#')
                    clean_line = line.strip().lstrip('#')
                    parts = clean_line.split("|", 1)
                    if len(parts) == 2:
                        channel_id, url = parts[0].strip(), parts[1].strip()
                        all_channels[channel_id] = {"url": url, "enabled": is_enabled}
    except FileNotFoundError:
        console.print(f"⚠️ Warning: '{channels_path}' not found.", style="bold yellow")
    return all_channels


def process_commands():
    cmd_dir = CONFIG["commands_dir"]
    if not os.path.exists(cmd_dir):
        os.makedirs(cmd_dir, exist_ok=True)

    for filename in os.listdir(cmd_dir):
        filepath = os.path.join(cmd_dir, filename)
        try:
            action, channel_id = filename.split('_')
            channel_id = channel_id.split('.')[0]
            console.print(f"  -> Executing command: [bold cyan]{action}[/bold cyan] for channel [bold magenta]{channel_id}[/bold magenta]")
            if action == "restart":
                if channel_id in FFMPEG_PROCESSES:
                    stop_ffmpeg_process(channel_id)
            elif action == "stop":
                stop_ffmpeg_process(channel_id)
            os.remove(filepath)
        except Exception as e:
            console.print(f"❌ Error processing command file {filename}: {e}", style="bold red")
            try:
                os.remove(filepath)
            except OSError:
                pass


def is_stream_stalled(channel_id, stall_threshold=45):
    m3u8_path = os.path.join(CONFIG["output_root"], f"channel_{channel_id}", "stream.m3u8")
    if not os.path.exists(m3u8_path):
        if channel_id in CHANNEL_START_TIMES and (datetime.now() - CHANNEL_START_TIMES[channel_id]).total_seconds() > stall_threshold:
            return True
        return False
    if (time.time() - os.path.getmtime(m3u8_path)) > stall_threshold:
        STALLED_CHANNELS.add(channel_id)
        return True
    STALLED_CHANNELS.discard(channel_id)
    return False


def get_bitrate_kbps(channel_id):
    try:
        channel_dir = os.path.join(CONFIG["output_root"], f"channel_{channel_id}")
        ts_files = sorted([f for f in os.listdir(channel_dir) if f.endswith('.ts')])
        if len(ts_files) >= 2:
            total_size_bytes = sum(os.path.getsize(os.path.join(channel_dir, f)) for f in ts_files[-2:])
            return round((total_size_bytes * 8) / (2 * 4 * 1024))
    except Exception:
        return '---'
    return '---'


def format_uptime(channel_id):
    start_time = CHANNEL_START_TIMES.get(channel_id)
    if not start_time:
        return "---"
    seconds = int((datetime.now() - start_time).total_seconds())
    h, m, s = seconds // 3600, (seconds % 3600) // 60, seconds % 60
    return f"{h:02}:{m:02}:{s:02}"


def write_status_to_json():
    status_data = []
    all_channels = get_all_channels_from_file()
    for channel_id, data in all_channels.items():
        pid, bitrate, uptime, url = "---", "---", "---", data['url']
        restarts = RESTART_COUNTS.get(channel_id, 0)

        if not data["enabled"]:
            status_text, status_code = "Stopped", "stopped"
        elif channel_id in COOLDOWN_CHANNELS and time.time() < COOLDOWN_CHANNELS[channel_id]:
            status_text, status_code = "Cooldown", "stopped"
        elif channel_id in FFMPEG_PROCESSES:
            proc_info = FFMPEG_PROCESSES[channel_id]
            pid = str(proc_info['proc'].pid)
            if channel_id in STALLED_CHANNELS:
                status_text, status_code = "Stalled", "stalled"
            elif proc_info['proc'].poll() is None:
                status_text, status_code = "Running", "running"
                bitrate = get_bitrate_kbps(channel_id)
                uptime = format_uptime(channel_id)
            else:
                status_text, status_code = "Restarting...", "restarting"
        else:
            status_text, status_code = "Starting...", "starting"

        status_data.append({"id": channel_id, "status_text": status_text, "status_code": status_code, "pid": pid, "bitrate": bitrate, "uptime": uptime, "restarts": restarts, "url": url})

    json_path = os.path.join(CONFIG["output_root"], CONFIG["status_json_file"])
    os.makedirs(CONFIG["output_root"], exist_ok=True)
    try:
        with open(json_path, 'w', encoding='utf-8') as f:
            json.dump({"channels": status_data, "last_update": datetime.now().isoformat()}, f, ensure_ascii=False, indent=4)
    except Exception as e:
        console.print(f"❌ Could not write status.json: {e}", style="bold red")


def main_manager():
    console.print("🚀 Stream Manager Initialized.", style="bold blue")

    while True:
        try:
            process_commands()
            all_channels_info = get_all_channels_from_file()
            desired_channels = {cid: data['url'] for cid, data in all_channels_info.items() if data['enabled']}

            for channel_id, data in list(FFMPEG_PROCESSES.items()):
                if channel_id not in desired_channels:
                    stop_ffmpeg_process(channel_id)
                    continue

                process_crashed = data['proc'].poll() is not None
                process_stalled = is_stream_stalled(channel_id)

                if process_crashed or process_stalled:
                    reason = "crashed" if process_crashed else "stalled"
                    console.print(f"⚠️ Channel {channel_id} ({reason}). Restarting...", style="bold yellow")
                    RESTART_COUNTS[channel_id] = RESTART_COUNTS.get(channel_id, 0) + 1

                    if RESTART_COUNTS[channel_id] >= CONFIG["restart_threshold"]:
                        console.print(f"🚫 Channel {channel_id} failed too many times. Cooling down for {CONFIG['cooldown_time']}s.", style="red")
                        COOLDOWN_CHANNELS[channel_id] = time.time() + CONFIG["cooldown_time"]
                        RESTART_COUNTS[channel_id] = 0

                    stop_ffmpeg_process(channel_id)

            for channel_id, url in desired_channels.items():
                if channel_id not in FFMPEG_PROCESSES:
                    start_ffmpeg_process(channel_id, url)

            write_status_to_json()
            time.sleep(CONFIG['check_interval'])

        except KeyboardInterrupt:
            break
        except Exception as e:
            console.print(f"⚠️ Main loop error: {e}", style="yellow")
            time.sleep(5)

    console.print("\n🚦 Shutting down...", style="bold red")
    for channel_id in list(FFMPEG_PROCESSES.keys()):
        stop_ffmpeg_process(channel_id)


if __name__ == "__main__":
    if not shutil.which(CONFIG["ffmpeg_path"]) and not os.path.exists(CONFIG["ffmpeg_path"]):
        console.print(f"FATAL: FFmpeg not found at '{CONFIG['ffmpeg_path']}'", style="bold red on white")
        exit(1)

    http_thread = threading.Thread(target=run_http_server, daemon=True)
    http_thread.start()
    time.sleep(1)

    main_manager()
