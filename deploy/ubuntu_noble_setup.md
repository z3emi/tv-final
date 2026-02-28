# Ubuntu Noble 24.04 deployment notes

## 1) Python dependencies (APT only)
```bash
sudo apt update
sudo apt install -y python3-psutil python3-rich
```

## 2) Nginx + PHP-FPM config
```bash
sudo cp deploy/nginx/iptv.conf /etc/nginx/sites-available/iptv
sudo ln -sf /etc/nginx/sites-available/iptv /etc/nginx/sites-enabled/iptv
sudo nginx -t
sudo systemctl reload nginx
```

## 3) systemd service for stream manager
```bash
sudo cp deploy/systemd/stream_with_latency.service /etc/systemd/system/stream_with_latency.service
sudo systemctl daemon-reload
sudo systemctl enable --now stream_with_latency.service
sudo systemctl status stream_with_latency.service --no-pager
```
