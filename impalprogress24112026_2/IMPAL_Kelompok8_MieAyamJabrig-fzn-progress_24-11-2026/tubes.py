from datetime import datetime
from typing import List, Optional, Dict, Any
from enum import Enum

# ==========================================
# 1. ENUMERASI
# ==========================================
class RoleEnum(Enum):
    MAHASISWA = "mahasiswa"
    DOSEN = "dosen"
    ADMINISTRATOR = "administrator"

class StatusEnrolmentEnum(Enum):
    AKTIF = "aktif"
    SELESAI = "selesai"
    MUNDUR = "mundur"

class PenyediaEnum(Enum):
    ZOOM = "Zoom"
    MEET = "Google Meet"
    TEAMS = "Microsoft Teams"
    DRIVE = "Google Drive"

class StatusIntegrasiEnum(Enum):
    AKTIF = "aktif"
    NONAKTIF = "nonaktif"
    GAGAL = "gagal"

class StatusLogEnum(Enum):
    BERHASIL = "berhasil"
    GAGAL = "gagal"


# ==========================================
# 2. KUMPULAN PENGGUNA & SESI
# ==========================================
class User:
    def __init__(self, idPengguna: str, namaLengkap: str, email: str, role: RoleEnum):
        # Protected attributes (#)
        self._idPengguna: str = idPengguna
        self._namaLengkap: str = namaLengkap
        self._email: str = email
        self._role: RoleEnum = role
        self._statusAktif: bool = True
        self._createdAt: datetime = datetime.now()

    def getIdPengguna(self) -> str:
        return self._idPengguna

    def getNamaLengkap(self) -> str:
        return self._namaLengkap

    def getEmail(self) -> str:
        return self._email

    def getRole(self) -> RoleEnum:
        return self._role

    def getUserData(self) -> Dict[str, Any]:
        return {
            "idPengguna": self._idPengguna,
            "namaLengkap": self._namaLengkap,
            "email": self._email,
            "role": self._role.value if hasattr(self._role, 'value') else self._role,
            "statusAktif": self._statusAktif
        }


class HalamanLogin:
    def __init__(self):
        # Private attributes (-)
        self.__idSesi: str = ""
        self.__idPengguna: str = ""
        self.__waktuMasuk: datetime = datetime.now()
        self.__waktuKeluar: Optional[datetime] = None

    def buatSesi(self, idPengguna: str) -> str:
        self.__idPengguna = idPengguna
        self.__idSesi = f"SES{int(datetime.now().timestamp()) % 1000000:06d}"
        self.__waktuMasuk = datetime.now()
        return self.__idSesi

    def periksaSesi(self) -> bool:
        return bool(self.__idSesi and self.__idPengguna)

    def getIdSesi(self) -> str:
        return self.__idSesi

    def getIdPengguna(self) -> str:
        return self.__idPengguna

    def getWaktuMasuk(self) -> datetime:
        return self.__waktuMasuk


class Mahasiswa(User):
    def __init__(self, idPengguna: str, namaLengkap: str, email: str, nim: str, lecturerCode: str, studyProgram: str = "S1 Informatika", country: str = "Indonesia"):
        super().__init__(idPengguna, namaLengkap, email, RoleEnum.MAHASISWA)
        self.__nim: str = nim
        self.__country: str = country
        self.__studyProgram: str = studyProgram
        self.__lecturerCode: str = lecturerCode

    def lihatMataKuliah(self) -> List[str]:
        pass

    def unduhMateri(self, idMateri: str) -> bytes:
        pass

    def kirimTugas(self, idTugas: str, file: str) -> bool:
        pass

    def isiForum(self, idForum: str, pesan: str) -> bool:
        pass


class Dosen(User):
    def __init__(self, idPengguna: str, namaLengkap: str, email: str, nip: str, lecturerCode: str, bidangKeahlian: str):
        super().__init__(idPengguna, namaLengkap, email, RoleEnum.DOSEN)
        self.__nip: str = nip
        self.__lecturerCode: str = lecturerCode
        self.__bidangKeahlian: str = bidangKeahlian

    def unggahMateri(self, idMataKuliah: str, file: str) -> bool:
        pass

    def buatTugas(self, idMataKuliah: str, judul: str) -> str:
        pass

    def nilaiTugas(self, idSubmission: str, nilai: float) -> bool:
        pass

    def kelolaMataKuliah(self, idMataKuliah: str) -> bool:
        pass

    def isiForum(self, idForum: str, pesan: str) -> bool:
        pass


class Administrator(User):
    def __init__(self, idPengguna: str, namaLengkap: str, email: str, idAdmin: str):
        super().__init__(idPengguna, namaLengkap, email, RoleEnum.ADMINISTRATOR)
        self.__idAdmin: str = idAdmin

    def mengelolaPengguna(self) -> None:
        pass

    def mengaturIntegrasi(self) -> None:
        pass

    def mengaturNotifikasi(self) -> None:
        pass

    def unggahOCW(self) -> bool:
        pass


# ==========================================
# 3. KUMPULAN AKADEMIK & KURSUS
# ==========================================
class MainCourse:
    def __init__(self, idKatalog: str, namaKatalog: str, jumlahMataKuliah: int = 0):
        self._idKatalog: str = idKatalog
        self._namaKatalog: str = namaKatalog
        self._jumlahMataKuliah: int = jumlahMataKuliah

    def lihatDaftarMataKuliah(self) -> List[str]:
        pass


class MataKuliah:
    def __init__(self, idMataKuliah: str, kodeMataKuliah: str, nama: str, nipDosen: str, semester: int, idKatalog: str):
        self._idMataKuliah: str = idMataKuliah
        self._kodeMataKuliah: str = kodeMataKuliah
        self._nama: str = nama
        self._nipDosen: str = nipDosen
        self._semester: int = semester
        self._statusAktif: bool = True
        self._idKatalog: str = idKatalog

    def lihatMateri(self) -> list:
        pass

    def unduhMateri(self, idMateri: str) -> bytes:
        pass

    def lihatTugas(self) -> list:
        pass

    def kirimTugas(self, idTugas: str, file: str) -> bool:
        pass


class Enrolment:
    def __init__(self, idMataKuliah: str, nim: str, tanggalDaftar: datetime):
        self._idMataKuliah: str = idMataKuliah
        self._nim: str = nim
        self._tanggalDaftar: datetime = tanggalDaftar
        self._statusEnrolment: StatusEnrolmentEnum = StatusEnrolmentEnum.AKTIF

    def daftarkan(self) -> bool:
        pass

    def batalkan(self) -> bool:
        pass


class OpenCourseWare:
    def __init__(self, idMateri: str, idKatalog: str, idAdmin: str, judul: str, deskripsi: str, tautanFile: str):
        self._idMateri: str = idMateri
        self._idKatalog: str = idKatalog
        self._idAdmin: str = idAdmin
        self._judul: str = judul
        self._deskripsi: str = deskripsi
        self._tautanFile: str = tautanFile
        self._tanggalUnggah: datetime = datetime.now()

    def unggahMateri(self) -> bool:
        pass

    def unduhMateri(self) -> bytes:
        pass

    def hapusMateri(self) -> bool:
        pass


# ==========================================
# 4. KUMPULAN AKTIVITI KULIAH
# ==========================================
class Materi:
    def __init__(self, idMateri: str, idMataKuliah: str, minggu: int, judul: str, tautanFile: str, deskripsi: str = ""):
        self._idMateri: str = idMateri
        self._idMataKuliah: str = idMataKuliah
        self._minggu: int = minggu
        self._judul: str = judul
        self._deskripsi: str = deskripsi
        self._tautanFile: str = tautanFile
        self._tanggalUnggah: datetime = datetime.now()

    def unggahMateri(self) -> bool:
        pass

    def unduhMateri(self) -> bytes:
        pass


class Tugas:
    def __init__(self, idTugas: str, idMataKuliah: str, namaTugas: str, deskripsi: str, tanggalBuka: datetime, tenggat: datetime):
        self._idTugas: str = idTugas
        self._idMataKuliah: str = idMataKuliah
        self._namaTugas: str = namaTugas
        self._deskripsi: str = deskripsi
        self._tanggalBuka: datetime = tanggalBuka
        self._tenggat: datetime = tenggat
        self._fileTugas: str = ""

    def buatTugas(self) -> bool:
        pass

    def lihatTugas(self) -> dict:
        pass


class PengumpulanTugas:
    def __init__(self, idSubmission: str, idTugas: str, nim: str, fileKumpul: str):
        self._idSubmission: str = idSubmission
        self._idTugas: str = idTugas
        self._nim: str = nim
        self._fileKumpul: str = fileKumpul
        self._waktuKumpul: datetime = datetime.now()
        self._nilai: Optional[float] = None

    def kirimTugas(self) -> bool:
        pass

    def beriNilai(self, nilai: float) -> None:
        self._nilai = nilai


class ForumDiskusi:
    def __init__(self, idForum: str, idMataKuliah: str, idPengguna: str, topik: str, isiForum: str):
        self._idForum: str = idForum
        self._idMataKuliah: str = idMataKuliah
        self._idPengirim: str = idPengguna
        self._topik: str = topik
        self._isiForum: str = isiForum
        self._waktu: datetime = datetime.now()

    def buatForum(self) -> bool:
        pass

    def editForum(self) -> bool:
        pass

    def hapusForum(self) -> bool:
        pass


class BalasanForum:
    def __init__(self, idBalasan: str, idForum: str, idPengguna: str, isiPesan: str):
        self._idBalasan: str = idBalasan
        self._idForum: str = idForum
        self._idPengguna: str = idPengguna
        self._isiPesan: str = isiPesan
        self._waktu: datetime = datetime.now()

    def balasForum(self) -> bool:
        pass

    def hapusBalasan(self) -> bool:
        pass


# ==========================================
# 5. KUMPULAN CIRI SOKONGAN
# ==========================================
class Kalender:
    def __init__(self, idKalender: str, idPembuat: str, judul: str, tanggalMulai: datetime, tanggalSelesai: datetime, jenisKegiatan: str, idMataKuliah: Optional[str] = None, isGlobal: bool = False):
        self._idKalender: str = idKalender
        self._idPembuat: str = idPembuat
        self._idMataKuliah: Optional[str] = idMataKuliah
        self._judul: str = judul
        self._tanggalMulai: datetime = tanggalMulai
        self._tanggalSelesai: datetime = tanggalSelesai
        self._jenisKegiatan: str = jenisKegiatan
        self._isGlobal: bool = isGlobal

    def tambahKegiatan(self) -> bool:
        pass

    def ubahKegiatan(self) -> bool:
        pass

    def hapusKegiatan(self) -> bool:
        pass


class Notifikasi:
    def __init__(self, idNotifikasi: str, idPengguna: str, isiPesan: str, idTugas: Optional[str] = None):
        self._idNotifikasi: str = idNotifikasi
        self._idPengguna: str = idPengguna
        self._idTugas: Optional[str] = idTugas
        self._isiPesan: str = isiPesan
        self._waktuDikirim: datetime = datetime.now()
        self._sudahDibaca: bool = False

    def kirimNotifikasi(self) -> bool:
        pass

    def tandaiDibaca(self) -> None:
        self._sudahDibaca = True


class LogNotifikasi:
    def __init__(self, idLog: str, idNotifikasi: str, statusKirim: StatusLogEnum, keterangan: str):
        self._idLog: str = idLog
        self._idNotifikasi: str = idNotifikasi
        self._statusKirim: StatusLogEnum = statusKirim
        self._keterangan: str = keterangan
        self._waktuLog: datetime = datetime.now()

    def catatLog(self) -> None:
        pass


class IntegrasiEksternal:
    def __init__(self, idIntegrasi: str, idMataKuliah: str, idPengguna: str, penyedia: PenyediaEnum, judul: str, tautan: str, status: StatusIntegrasiEnum = StatusIntegrasiEnum.AKTIF):
        self._idIntegrasi: str = idIntegrasi
        self._idMataKuliah: str = idMataKuliah
        self._idPengguna: str = idPengguna
        self._penyedia: PenyediaEnum = penyedia
        self._judul: str = judul
        self._tautan: str = tautan
        self._status: StatusIntegrasiEnum = status

    def hubungkanLayanan(self) -> bool:
        pass

    def simpanTautan(self) -> bool:
        pass