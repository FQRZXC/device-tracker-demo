#!/bin/bash

echo "=== Установка Device Tracker Demo ==="
echo "Этот скрипт установит необходимые компоненты для демонстрации"
echo "ВНИМАНИЕ: Используйте только в образовательных целях!"
echo "-----------------------------------------------------"

# Проверяем наличие PHP
if ! command -v php &> /dev/null; then
    echo "PHP не установлен. Устанавливаем..."
    sudo apt update
    sudo apt install -y php-cli
else
    echo "PHP уже установлен."
fi

# Проверяем наличие Cloudflared (опционально)
if ! command -v cloudflared &> /dev/null; then
    echo "Cloudflared не установлен. Хотите установить для доступа через интернет? (y/n)"
    read install_cloudflared
    
    if [ "$install_cloudflared" = "y" ]; then
        echo "Устанавливаем Cloudflared..."
        # Для Debian/Ubuntu/Kali
        curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
        sudo dpkg -i cloudflared.deb
        rm cloudflared.deb
        
        echo "Cloudflared установлен. Для настройки туннеля выполните:"
        echo "cloudflared login"
        echo "cloudflared tunnel create device-tracker"
        echo "cloudflared tunnel route dns device-tracker [ваш-поддомен]"
    fi
else
    echo "Cloudflared уже установлен."
fi

# Создаем директорию для логов
mkdir -p logs
touch logs/visitors.log
chmod 666 logs/visitors.log

echo "Установка завершена!"
echo "Для запуска демонстрации выполните: ./run.sh"
