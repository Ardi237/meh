"""Show a side-by-side comparison of Evan's Laravel project and the revision."""

from __future__ import annotations

import difflib
import hashlib
from dataclasses import dataclass
from pathlib import Path

import streamlit as st


st.set_page_config(page_title="Perbandingan Revisi Laravel", page_icon="↔", layout="wide")

BASE_DIR = Path(__file__).resolve().parent
OLD_PROJECT = BASE_DIR / "data" / "evan_asli"
NEW_PROJECT = BASE_DIR / "data" / "evan_revisi"

TEXT_EXTENSIONS = {
    ".blade.php", ".css", ".csv", ".env", ".example", ".html", ".js",
    ".json", ".md", ".php", ".py", ".sql", ".svg", ".ts", ".txt",
    ".vue", ".xml", ".yaml", ".yml",
}

LANGUAGES = {
    ".blade.php": "php", ".css": "css", ".html": "html", ".js": "javascript",
    ".json": "json", ".md": "markdown", ".php": "php", ".py": "python",
    ".sql": "sql", ".ts": "typescript", ".xml": "xml", ".yaml": "yaml", ".yml": "yaml",
}


@dataclass(frozen=True)
class ProjectFile:
    data: bytes
    size: int
    digest: str


def file_key(path: str) -> str:
    return ".blade.php" if path.endswith(".blade.php") else Path(path).suffix.lower()


def is_text_file(path: str, data: bytes) -> bool:
    return file_key(path) in TEXT_EXTENSIONS or b"\x00" not in data[:1024]


def decode_file(data: bytes) -> str | None:
    for encoding in ("utf-8-sig", "utf-8"):
        try:
            return data.decode(encoding)
        except UnicodeDecodeError:
            continue
    return None


@st.cache_data(show_spinner=False)
def read_project(project_folder: str) -> dict[str, ProjectFile]:
    root = Path(project_folder)
    project_files: dict[str, ProjectFile] = {}
    for file_path in root.rglob("*"):
        if file_path.is_file():
            data = file_path.read_bytes()
            relative_path = file_path.relative_to(root).as_posix()
            project_files[relative_path] = ProjectFile(data, len(data), hashlib.sha256(data).hexdigest())
    return project_files


def status_for(old: ProjectFile | None, new: ProjectFile | None) -> str:
    if old is None:
        return "Hanya di revisi"
    if new is None:
        return "Hanya di asli"
    return "Diubah" if old.digest != new.digest else "Sama"


def show_version(file: ProjectFile | None, path: str, empty_message: str) -> None:
    if file is None:
        st.caption(empty_message)
        return
    if not is_text_file(path, file.data):
        st.info(f"File biner — {file.size:,} byte. Isi tidak ditampilkan.")
        return
    if file.size > 1_500_000:
        st.warning(f"File teks besar ({file.size:,} byte), pratinjau disembunyikan.")
        return
    text = decode_file(file.data)
    if text is None:
        st.info("Encoding file tidak dapat dibaca sebagai teks.")
        return
    st.code(text, language=LANGUAGES.get(file_key(path)), line_numbers=True)


def show_diff(old: ProjectFile | None, new: ProjectFile | None, path: str) -> None:
    if old is None or new is None or not is_text_file(path, old.data) or not is_text_file(path, new.data):
        return
    old_text, new_text = decode_file(old.data), decode_file(new.data)
    if old_text is None or new_text is None:
        return
    changes = "".join(
        difflib.unified_diff(
            old_text.splitlines(keepends=True),
            new_text.splitlines(keepends=True),
            fromfile="Evan — asli", tofile="Evan — revisi",
        )
    )
    if changes:
        st.code(changes, language="diff")


st.markdown(
    """
    <style>
      .hero { padding: 0.3rem 0 0.9rem; }
      .hero h1 { margin: 0; font-size: 2rem; }
      .hero p { margin: 0.25rem 0 0; color: #64748b; }
      .label { font-size: 1rem; font-weight: 750; }
      .status { display: inline-block; padding: 0.17rem 0.55rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700; }
      .changed { background: #fef3c7; color: #92400e; }
      .added { background: #dcfce7; color: #166534; }
      .deleted { background: #fee2e2; color: #991b1b; }
    </style>
    <div class="hero">
      <h1>↔ Perbandingan Revisi Laravel</h1>
      <p>Versi asli Evan di kiri, hasil revisi Evan di kanan. Tidak perlu memilih ZIP lagi.</p>
    </div>
    """,
    unsafe_allow_html=True,
)

if not OLD_PROJECT.exists() or not NEW_PROJECT.exists():
    st.error("Folder snapshot proyek tidak ditemukan. Pastikan folder data tersedia di repository.")
    st.stop()

old_files = read_project(str(OLD_PROJECT))
new_files = read_project(str(NEW_PROJECT))
all_paths = sorted(set(old_files) | set(new_files), key=str.lower)
comparison = [(path, status_for(old_files.get(path), new_files.get(path))) for path in all_paths]
changed_files = [(path, status) for path, status in comparison if status != "Sama"]
counts = {status: sum(1 for _, value in comparison if value == status) for status in ("Diubah", "Hanya di revisi", "Hanya di asli", "Sama")}

left_header, right_header = st.columns(2)
with left_header:
    st.markdown("<div class='label'>← Laravel asli Evan</div>", unsafe_allow_html=True)
    st.caption(f"{len(old_files)} file source")
with right_header:
    st.markdown("<div class='label'>Revisi Evan →</div>", unsafe_allow_html=True)
    st.caption(f"{len(new_files)} file source")

metric_one, metric_two, metric_three, metric_four = st.columns(4)
metric_one.metric("Diubah", counts["Diubah"])
metric_two.metric("Hanya di revisi", counts["Hanya di revisi"])
metric_three.metric("Hanya di asli", counts["Hanya di asli"])
metric_four.metric("Sama", counts["Sama"])

st.divider()
filters = st.multiselect(
    "Jenis perubahan", ["Diubah", "Hanya di revisi", "Hanya di asli"],
    default=["Diubah", "Hanya di revisi", "Hanya di asli"],
)
show_line_diff = st.toggle("Tampilkan perbedaan per baris", value=False)
visible_files = [(path, status) for path, status in changed_files if status in filters]

if not visible_files:
    st.success("Tidak ada file pada filter ini.")
    st.stop()

st.caption(f"Menampilkan {len(visible_files)} dari {len(changed_files)} file yang berubah.")
css_class = {"Diubah": "changed", "Hanya di revisi": "added", "Hanya di asli": "deleted"}

for path, status in visible_files:
    with st.expander(f"{status} — {path}"):
        st.markdown(f"<span class='status {css_class[status]}'>{status}</span>", unsafe_allow_html=True)
        old_column, new_column = st.columns(2)
        with old_column:
            st.markdown("**Laravel asli Evan**")
            show_version(old_files.get(path), path, "File belum ada pada versi lama.")
        with new_column:
            st.markdown("**Revisi Evan**")
            show_version(new_files.get(path), path, "File tidak ada pada hasil revisi.")
        if show_line_diff:
            st.markdown("**Perbedaan baris**")
            show_diff(old_files.get(path), new_files.get(path), path)
