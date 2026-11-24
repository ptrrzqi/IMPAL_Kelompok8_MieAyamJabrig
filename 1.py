import os
from datetime import datetime
from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import mysql.connector

app = Flask(__name__)
CORS(app)

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'celoe_lms',
    'port': 3306
}

def get_db_connection():
    return mysql.connector.connect(**DB_CONFIG)

def init_database_tables():
    try:
        conn = get_db_connection()
        cursor = conn.cursor()

        cursor.execute("""
        CREATE TABLE IF NOT EXISTS user (
            idPengguna VARCHAR(50) PRIMARY KEY,
            namaLengkap VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL,
            statusAktif TINYINT DEFAULT 1,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        """)

        cursor.execute("""
        CREATE TABLE IF NOT EXISTS halamanlogin (
            idSesi VARCHAR(50) PRIMARY KEY,
            idPengguna VARCHAR(50) NOT NULL,
            waktuMasuk DATETIME DEFAULT CURRENT_TIMESTAMP,
            waktuKeluar DATETIME NULL,
            FOREIGN KEY (idPengguna) REFERENCES user(idPengguna) ON DELETE CASCADE
        );
        """)

        cursor.execute("""
        CREATE TABLE IF NOT EXISTS Tugas (
            idTugas VARCHAR(50) PRIMARY KEY,
            idMataKuliah VARCHAR(50) NOT NULL,
            namaTugas VARCHAR(255) NOT NULL,
            deskripsi TEXT,
            dibukaHingga DATETIME NOT NULL,
            fileTugas VARCHAR(255) NULL
        );
        """)

        cursor.execute("""
        CREATE TABLE IF NOT EXISTS PengumpulanTugas (
            idSubmission VARCHAR(50) PRIMARY KEY,
            idTugas VARCHAR(50) NOT NULL,
            nim VARCHAR(50) NOT NULL,
            fileKumpul VARCHAR(255) NOT NULL,
            waktuKumpul DATETIME NOT NULL,
            nilai DECIMAL(5,2) DEFAULT 0.0,
            FOREIGN KEY (idTugas) REFERENCES Tugas(idTugas) ON DELETE CASCADE,
            FOREIGN KEY (nim) REFERENCES user(idPengguna) ON DELETE CASCADE
        );
        """)

        cursor.execute("""
        INSERT INTO user (idPengguna, namaLengkap, email, password, role, statusAktif) VALUES
        ('103012400120', 'Fauzan Ahmad Choiry', 'fauzan@student.telkomuniversity.ac.id', '12345678', 'Mahasiswa', 1),
        ('198703152014041001', 'Dr. Ir. Budi Santoso, M.T.', 'budi@telkomuniversity.ac.id', '12345678', 'Dosen', 1),
        ('admin01', 'Administrator CeLOE', 'admin@telkomuniversity.ac.id', '12345678', 'Administrator', 1)
        ON DUPLICATE KEY UPDATE namaLengkap = VALUES(namaLengkap);
        """)

        cursor.execute("""
        INSERT INTO Tugas (idTugas, idMataKuliah, namaTugas, deskripsi, dibukaHingga)
        VALUES ('TUGAS-IF4601-01', 'IF-46-01', 'Analisis Dampak Big Data dan AI pada Sistem Digital', 'Analisis kepatuhan regulasi, etika, dan dampak sosial pemanfaatan AI pada sistem digital modern.', '2026-12-31 23:59:59')
        ON DUPLICATE KEY UPDATE namaTugas = VALUES(namaTugas);
        """)

        conn.commit()
        cursor.close()
        conn.close()
        print("[Database] Seluruh tabel (user, halamanlogin, Tugas, PengumpulanTugas) siap digunakan.")
    except mysql.connector.Error as err:
        print(f"[Database Error] Inisialisasi gagal: {err}")


# ==============================================================================
# ROUTE HALAMAN WEB UTAMA (Agar bisa dibuka lewat http://127.0.0.1:5000)
# ==============================================================================
@app.route('/')
def serve_web():
    return send_from_directory('.', 'beda.html')


# ==============================================================================
# ENDPOINT API BACKEND
# ==============================================================================
@app.route('/api/register', methods=['POST'])
def api_register():
    data = request.get_json() or {}
    id_pengguna = (data.get('username') or '').strip()
    nama_lengkap = (data.get('namaLengkap') or '').strip()
    email = (data.get('email') or '').strip()
    password = data.get('password') or ''
    role = data.get('role', 'Mahasiswa')

    if not id_pengguna or not nama_lengkap or not email or not password:
        return jsonify({"status": "failed", "message": "Semua kolom pendaftaran wajib diisi!"}), 400

    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)

        cursor.execute("SELECT idPengguna FROM user WHERE idPengguna = %s OR email = %s", (id_pengguna, email))
        if cursor.fetchone():
            cursor.close()
            conn.close()
            return jsonify({"status": "failed", "message": "NIM/ID Pengguna atau Email sudah terdaftar!"}), 409

        query_insert = """
            INSERT INTO user (idPengguna, namaLengkap, email, password, role, statusAktif, createdAt)
            VALUES (%s, %s, %s, %s, %s, 1, NOW())
        """
        cursor.execute(query_insert, (id_pengguna, nama_lengkap, email, password, role))
        conn.commit()

        cursor.close()
        conn.close()
        print(f"[phpMyAdmin] User Baru Didaftarkan: {id_pengguna} - {nama_lengkap} ({role})")
        return jsonify({"status": "success", "message": f"Akun {role} berhasil dibuat! Silakan masuk."}), 201

    except mysql.connector.Error as err:
        return jsonify({"status": "failed", "message": f"Koneksi MySQL gagal: {err}"}), 500


@app.route('/api/login', methods=['POST'])
def api_login():
    data = request.get_json() or {}
    identifier = (data.get('username') or '').strip()
    password_input = data.get('password') or ''

    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)

        query_check = """
            SELECT idPengguna, namaLengkap, email, role, statusAktif 
            FROM user 
            WHERE (idPengguna = %s OR email = %s) AND password = %s
        """
        cursor.execute(query_check, (identifier, identifier, password_input))
        user_row = cursor.fetchone()

        if user_row:
            id_pengguna = user_row['idPengguna']
            nama = user_row['namaLengkap']
            role = user_row['role']
            email = user_row['email']
            id_sesi = f"SESI-{id_pengguna}-{int(datetime.now().timestamp())}"
            waktu_login = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

            query_insert = """
                INSERT INTO halamanlogin (idSesi, idPengguna, waktuMasuk) 
                VALUES (%s, %s, %s)
            """
            cursor.execute(query_insert, (id_sesi, id_pengguna, waktu_login))
            conn.commit()

            cursor.close()
            conn.close()

            print(f"[phpMyAdmin] Login Sukses: Sesi {id_sesi} disimpan untuk {nama}")
            return jsonify({
                "status": "success",
                "idSesi": id_sesi,
                "user": {
                    "idPengguna": id_pengguna,
                    "namaLengkap": nama,
                    "email": email,
                    "role": role
                }
            }), 200

        cursor.close()
        conn.close()
        return jsonify({"status": "failed", "message": "NIM/Username atau kata sandi tidak valid!"}), 401

    except mysql.connector.Error as err:
        return jsonify({"status": "failed", "message": f"Koneksi MySQL gagal: {err}"}), 500


@app.route('/api/logout', methods=['POST'])
def api_logout():
    data = request.get_json() or {}
    id_sesi = data.get('idSesi')
    if not id_sesi:
        return jsonify({"status": "success"}), 200

    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        waktu_keluar = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        cursor.execute("UPDATE halamanlogin SET waktuKeluar = %s WHERE idSesi = %s", (waktu_keluar, id_sesi))
        conn.commit()
        cursor.close()
        conn.close()
        return jsonify({"status": "success", "message": "Sesi logout tercatat."}), 200
    except mysql.connector.Error as err:
        return jsonify({"status": "failed", "message": str(err)}), 500


@app.route('/api/tugas/submit', methods=['POST'])
def api_submit_tugas():
    data = request.get_json() or {}
    id_tugas = data.get('idTugas', 'TUGAS-IF4601-01')
    nim = data.get('nim')
    file_kumpul = data.get('fileKumpul', 'Tugas1_Laporan.pdf')

    if not nim:
        return jsonify({"status": "failed", "message": "NIM Mahasiswa wajib disertakan!"}), 400

    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)

        cursor.execute("SELECT dibukaHingga, namaTugas FROM Tugas WHERE idTugas = %s", (id_tugas,))
        tugas = cursor.fetchone()

        if not tugas:
            cursor.close()
            conn.close()
            return jsonify({"status": "failed", "message": "Data tugas tidak ditemukan!"}), 404

        sekarang = datetime.now()
        tenggat = tugas['dibukaHingga']

        if sekarang > tenggat:
            cursor.close()
            conn.close()
            return jsonify({"status": "failed", "message": "Tenggat waktu pengumpulan telah berakhir!"}), 400

        id_submission = f"SUB-{nim}-{int(sekarang.timestamp())}"
        waktu_str = sekarang.strftime("%Y-%m-%d %H:%M:%S")

        query_insert = """
            INSERT INTO PengumpulanTugas (idSubmission, idTugas, nim, fileKumpul, waktuKumpul, nilai)
            VALUES (%s, %s, %s, %s, %s, 0.0)
        """
        cursor.execute(query_insert, (id_submission, id_tugas, nim, file_kumpul, waktu_str))
        conn.commit()

        cursor.close()
        conn.close()

        print(f"[phpMyAdmin] Submission Tugas Sukses: {id_submission} oleh NIM {nim}")
        return jsonify({
            "status": "success",
            "message": "Tugas Anda berhasil diserahkan ke sistem CeLOE!",
            "idSubmission": id_submission,
            "waktuKumpul": waktu_str,
            "namaTugas": tugas['namaTugas']
        }), 200

    except mysql.connector.Error as err:
        return jsonify({"status": "failed", "message": f"Koneksi MySQL gagal: {err}"}), 500


@app.route('/api/tugas/submissions', methods=['GET'])
def api_list_submissions():
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT p.idSubmission, p.idTugas, t.namaTugas, p.nim, u.namaLengkap, p.fileKumpul, p.waktuKumpul, p.nilai
            FROM PengumpulanTugas p
            JOIN user u ON p.nim = u.idPengguna
            JOIN Tugas t ON p.idTugas = t.idTugas
            ORDER BY p.waktuKumpul DESC
        """)
        rows = cursor.fetchall()
        for r in rows:
            if isinstance(r['waktuKumpul'], datetime):
                r['waktuKumpul'] = r['waktuKumpul'].strftime("%Y-%m-%d %H:%M:%S")
        cursor.close()
        conn.close()
        return jsonify({"status": "success", "data": rows}), 200
    except mysql.connector.Error as err:
        return jsonify({"status": "failed", "message": str(err)}), 500


if __name__ == '__main__':
    init_database_tables()
    print("==================================================")
    print("CeLOE LMS Web & Backend API Aktif")
    print("Database : celoe_lms (MySQL localhost:3306)")
    print("Buka Web : http://127.0.0.1:5000")
    print("==================================================")
    app.run(host='127.0.0.1', port=5000, debug=True)