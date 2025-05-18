#!/bin/bash

echo "=== Запуск туннеля Cloudflare ==="
echo "Это позволит получить доступ к демонстрации через интернет"
echo "-----------------------------------------------------"

# Проверяем наличие Cloudflared
if ! command -v cloudflared &> /dev/null; then
    echo "Cloudflared не установлен. Сначала выполните ./install.sh"
    exit 1
fi

# Проверяем наличие туннеля
if ! cloudflared tunnel list | grep -q "device-tracker"; then
    echo "Туннель 'device-tracker' не найден. Создаем..."
    cloudflared tunnel create device-tracker
    
    echo "Для настройки DNS выполните:"
    echo "cloudflared tunnel route dns device-tracker [ваш-поддомен]"
    echo "Например: cloudflared tunnel route dns device-tracker demo.example.com"
fi

# Запускаем туннель
echo "Запуск туннеля..."
echo "Для остановки нажмите Ctrl+C"

# Создаем временный конфиг для туннеля
TUNNEL_ID=$(cloudflared tunnel list | grep device-tracker | awk '{print $1}')
echo "tunnel: $TUNNEL_ID" > tunnel_config.yml
echo "credentials-file: ~/.cloudflared/$TUNNEL_ID.json" >> tunnel_config.yml
echo "ingress:" >> tunnel_config.yml
echo "  - hostname: auto" >> tunnel_config.yml
echo "    service: http://localhost:8080" >> tunnel_config.yml
echo "  - service: http_status:404" >> tunnel_config.yml

# Запускаем туннель
cloudflared tunnel --config tunnel_config.yml run

# Удаляем временный конфиг
rm tunnel_config.yml
