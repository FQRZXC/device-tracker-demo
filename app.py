from flask import Flask, render_template, request, jsonify
from ip2geotools.databases.noncommercial import DbIpCity
from user_agents import parse
import webbrowser
import os

app = Flask(__name__)

def get_geo_by_ip(ip):
    try:
        response = DbIpCity.get(ip, api_key='free')
        return {
            "city": response.city,
            "country": response.country,
            "latitude": response.latitude,
            "longitude": response.longitude,
            "accuracy": "Низкая (по IP)"
        }
    except:
        return None

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/api/save_data', methods=['POST'])
def save_data():
    user_ip = request.remote_addr
    geo_ip = get_geo_by_ip(user_ip)
    user_agent = parse(request.headers.get('User-Agent'))
    consent = request.json.get('consent', False)
    
    print("\n=== Данные пользователя ===")
    print(f"IP: {user_ip}")
    
    if geo_ip:
        print(f"📍 Город: {geo_ip['city']}, {geo_ip['country']}")
        print(f"🌐 Координаты (IP): {geo_ip['latitude']}, {geo_ip['longitude']}")
        print(f"📏 Точность: {geo_ip['accuracy']}")
    
    if consent:
        gps_coords = request.json.get('gps')
        if gps_coords:
            print(f"🎯 Точные координаты (GPS): {gps_coords['lat']}, {gps_coords['lng']}")
            print(f"📡 Точность: ±{gps_coords['accuracy']} метров")
        
        print("\n📱 Устройство:")
        print(f"🖥 Браузер: {user_agent.browser.family} {user_agent.browser.version_string}")
        print(f"⚙️ ОС: {user_agent.os.family} {user_agent.os.version_string}")
        print(f"📱 Девайс: {user_agent.device.family}")
        print(f"🖥 Экран: {request.json.get('screen', 'N/A')}")
    
    print("==========================")
    return jsonify({"status": "OK"})

if __name__ == '__main__':
    webbrowser.open("http://localhost:5000")  # Автооткрытие браузера
    app.run(host='0.0.0.0', port=5000)
