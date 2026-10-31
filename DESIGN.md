---
name: Bank Sampah Digital Desa - Design System V2
description: Go Green Village - Nature-inspired design system for village waste bank digital platform
version: 2.1
creativeNorthStar: Go Green Village
colors:
  deep-canopy: "#1B5E20"
  sidebar-green: "#2A732E"
  deep-forest: "#164D1A"
  fresh-leaf: "#2E7D32"
  spring-leaf: "#43A047"
  new-growth: "#66BB6A"
  morning-dew: "#A5D6A7"
  leaf-pale: "#E8F5E9"
  leaf-wash: "#F1F8E9"
  warm-paper: "#FDFBF7"
  clean-white: "#FFFFFF"
  soft-soil: "#F5F5F0"
  ink: "#1F2933"
  ink-secondary: "#3D4A3C"
  ink-muted: "#5F6B60"
  ink-faint: "#78837A"
  line: "#E8EBE4"
  line-soft: "#F0F2ED"
  amber-deep: "#B45309"
  amber-wash: "#FEF3C7"
  rose-deep: "#B91C1C"
  rose-wash: "#FEE2E2"
  sky-deep: "#1D4ED8"
  sky-wash: "#DBEAFE"
  scrim: "rgba(20, 30, 22, 0.45)"
  sidebar-shadow: "rgba(20, 40, 24, 0.18)"
  danger-hover: "rgba(220, 80, 80, 0.18)"
  danger-text: "#FFE4E4"
  danger-text-dim: "rgba(255, 214, 214, 0.75)"
  success-text: "#14532D"
  success-border: "#FECACA"
  warning-border: "#FDE68A"
  info-border: "#BFDBFE"
  border-hover: "#DCDFD6"
  legacy-amber-wash: "#FFF3E0"
  legacy-rose-wash: "#FFEBEE"
  legacy-sky-wash: "#E3F2FD"
typography:
  font-sans: "'Plus Jakarta Sans', 'Segoe UI', system-ui, -apple-system, sans-serif"
---

# BANK SAMPAH DIGITAL DESA - DESIGN SYSTEM V2
# Go Green Village

## Creative North Star

**Go Green Village**

Aplikasi ini adalah representasi digital dari sebuah desa yang sedang membangun budaya hidup hijau. Setiap elemen visual harus terasa seperti: lingkungan hidup, pertumbuhan, kebersihan, komunitas, dan kepercayaan.

---

## Prinsip Desain Utama

### 1. Nature-First
Visual terinspirasi dari alam dan lingkungan. Warna hijau bukan hanya warna brand, ini adalah komitmen. Gunakan elemen natural secara intentional untuk brand recognition.

### 2. Human-First
Desain harus mudah dipahami oleh masyarakat desa dengan berbagai tingkat literasi digital. Bahasa Indonesia yang jelas dan langsung ke inti.

### 3. Action-First
Dashboard membantu pengguna menemukan jawaban:
- Berapa tabungan saya?
- Berapa yang bisa saya cairkan?
- Apa yang harus saya lakukan selanjutnya?

### 4. Trust-First
Informasi finansial harus sangat jelas dan dapat dipercaya.

### 5. Mobile-First
Prioritaskan pengalaman smartphone. Masyarakat mengakses melalui HP.

### 6. Calm Interface
Gunakan ruang putih yang cukup. Tidak semua elemen harus terlihat penting.

### 7. Sustainable Visual Identity
Elemen daun/tumbuhan menjadi bagian dari identitas visual, bukan dekorasi acak.

---

## Brand Personality

### Emosi yang Ingin Dirasakan
- Tumbuh - Tabungan yang bertambah
- Bersih - Lingkungan yang rapi
- Aman - Tempat yang terpercaya
- Ramah - Mudah digunakan
- Komunitas - Bagian dari gerakan bersama

---

## Color Palette

### Primary Colors - The Greens

| Name | Hex | Usage |
|-------|-----|-------|
| Forest Canopy | #1B5E20 | Kedalaman, headline, hover |
| Fresh Leaf | #2E7D32 | Primary brand, CTA utama |
| Spring Leaf | #43A047 | Light accent |
| New Growth | #66BB6A | Success indicators |
| Morning Dew | #A5D6A7 | Borders, highlights |

### Secondary Colors - Nature Neutrals

| Name | Hex | Usage |
|-------|-----|-------|
| Warm Paper | #FDFBF7 | Primary background |
| Clean White | #FFFFFF | Card backgrounds |
| Soft Soil | #F5F5F0 | Secondary background |
| Moss Gray | #6B7B6E | Secondary text |
| Earth Dark | #3D4A3C | Body text |

### Semantic Colors - Status

| Status | Hex | Usage |
|--------|-----|-------|
| Success | #2E7D32 | Saldo positif |
| Growth | #43A047 | Progress |
| Warning | #F57C00 | Pending |
| Error | #D32F2F | Error, penolakan |
| Info | #1565C0 | Informasi |

### Surface Tokens - Dashboard Nasabah (V2.1)

Token tambahan yang dipakai pada Dashboard Nasabah. Semua tetap di dalam
komitmen hijau & putih; hanya ada perluasan ramp agar kontras teks kecil
tetap memenuhi WCAG AA (4.5:1).

| Name | Hex | Usage |
|------|-----|-------|
| Deep Canopy | #1B5E20 | Ujung gradient sidebar & kartu saldo |
| Sidebar Green | #2A732E | Awal gradient sidebar |
| Deep Forest | #164D1A | Ujung bawah gradient sidebar |
| Leaf Pale | #E8F5E9 | Ikon chip, badge success |
| Leaf Wash | #F1F8E9 | Latar badge success, hover |
| Ink | #1F2933 | Judul halaman, nilai saldo |
| Ink Secondary | #3D4A3C | Body text |
| Ink Muted | #5F6B60 | Teks sekunder 13px+ |
| Ink Faint | #78837A | Ikon dekoratif saja (bukan teks) |
| Line | #E8EBE4 | Border kartu, garis tabel |
| Line Soft | #F0F2ED | Pemisah baris tabel |
| Amber Deep | #B45309 | Badge warning, nilai "dalam pengajuan" |
| Amber Wash | #FEF3C7 | Latar badge warning |
| Rose Deep | #B91C1C | Badge danger, flash error |
| Rose Wash | #FEE2E2 | Latar badge danger |
| Sky Deep | #1D4ED8 | Badge info, flash info |
| Sky Wash | #DBEAFE | Latar badge info |
| Scrim | rgba(20,30,22,0.45) | Overlay saat sidebar terbuka (mobile/tablet) |
| Sidebar Shadow | rgba(20,40,24,0.18) | Bayangan sidebar di mode overlay |

---

## Typography

### Font Family

| Role | Face | Fallback |
|------|------|----------|
| UI & Body | Plus Jakarta Sans | Segoe UI, system-ui, sans-serif |

Satuan sans modern, weight 400/500/600/700. Dipakai untuk seluruh UI
(titel, body, label, tabel, angka) — satu keluarga agar hierarki
ditopang ukuran dan weight, bukan oleh pemakaian font berbeda.

### Type Scale

| Level | Size | Weight | Usage |
|-------|------|--------|-------|
| Display | 32px | 700 | Hero headlines |
| Headline | 24px | 700 | Page titles |
| Title | 20px | 600 | Section headings |
| Body Large | 18px | 400 | Lead text |
| Body | 16px | 400 | Default text |
| Label | 14px | 600 | Labels, badges |
| Caption | 12px | 400 | Helper text |

Angka finansial memakai `font-variant-numeric: tabular-nums` agar kolom
rupiah sejajar dan mudah dibandingkan antar baris.

---

## Spacing System

Base Unit: 4px

| Token | Value | Usage |
|-------|-------|-------|
| xs | 4px | Micro spacing |
| sm | 8px | Inline elements |
| md | 16px | Standard spacing |
| lg | 24px | Section spacing |
| xl | 32px | Major gaps |
| 2xl | 48px | Hero spacing |

---

## Shape System

| Element | Radius | Feel |
|---------|--------|------|
| Buttons | 8px | Friendly |
| Inputs | 8px | Consistent |
| Cards | 12px | Soft, elevated |
| Hero | 16px | Organic |
| Badges | 20px | Status pill |

### Radius - Dashboard Nasabah (V2.1)

| Element | Radius | Token |
|---------|--------|-------|
| Tombol, chip ikon | 8px / 10px | `--r-sm` / `--r-md` |
| Kartu konten | 16px | `--r-lg` |
| Kartu saldo, panel | 20px | `--r-xl` |
| Badge status | 999px | pill |
| Avatar | 50% | lingkaran |

---

## Components

### Navigation - Sidebar (Drawer)
- Background: `linear-gradient(168deg, #2A732E 0%, #1B5E20 62%, #164D1A 100%)`
- Width: 272px (`--sidebar-w`)
- Desktop (>1200px): sidebar permanen, konten digeser `margin-left: 272px`
- Tablet & Mobile (<=1200px): off-canvas drawer, dibuka lewat hamburger di topbar
- Tombol hamburger: 46x46px, ikon 3 garis yang berotasi jadi X saat terbuka
- Overlay `rgba(20,30,22,0.45)` + backdrop blur saat drawer terbuka
- Tutup dengan: klik overlay, tombol X, atau tombol Escape
- `aria-expanded` pada tombol, `aria-controls` menunjuk ke drawer
- Active state: latar `rgba(255,255,255,0.16)` + font weight 600
- Navigasi dikelompokkan (Menu Utama / Keuangan / Akun) dengan label kapital
- Link navigasi minimal 46px tinggi (target sentuh)

### Topbar
- Height: 72px, sticky, background putih translucent + backdrop blur
- Memuat: tombol hamburger, judul halaman, chip user (avatar + nama + peran)
- Avatar 40px, inisial huruf kapital, latar Leaf Pale

### Balance Card
- 3 kartu: Total Tabungan (gradient hijau, kartu utama, grid 1.35fr),
  Bisa Dicairkan (putih, angka hijau), Dalam Pengajuan (putih, angka amber)
- Angka ditampilkan lebih dulu, label di atas sebagai konteks
- Nilai utama 36px/700, nilai sekunder 28px/700
- Hover: `translateY(-2px)` + shadow naik satu tingkat
- Angka tabular agar sejajar antar kartu

### Transaction Preview
- Card container
- Header with action link
- Table preview 3-5 items

### Empty State
- Centered layout
- Icon in circle #E8F5E9
- Heading + description + CTA

### Status Badge
- Pill shape 20px radius
- Variants: success, warning, error, info, neutral

### Form Input
- Border: #D4D4CF
- Focus: #2E7D32 with glow
- Error: #D32F2F

### Form Button
- Primary: #2E7D32
- Secondary: #1565C0
- Touch target: 44px min

### Data Table
- Header: #F5F5F0 background
- Hover: #FAFAF7
- Mobile: horizontal scroll

### Alert
- Border-left: 4px solid
- Variants: success, error, warning, info

### Modal
- Overlay: rgba(0,0,0,0.5)
- Border-radius: 16px
- Max-width: 480px

### Icon System
- Semua ikon adalah SVG inline, stroke 1.75, `stroke-linecap: round`
- Satu helper `icon($name, $class)` mengembalikan markup SVG
- `aria-hidden="true"` + `focusable="false"` — ikon dekoratif, bukan informatif
- Emoji TIDAK boleh dipakai sebagai pengganti ikon

---

## Decorative System

### Leaf Motif Rules

DO:
- Hero section
- Empty states
- Auth pages
- Opacity 0.05-0.15 for backgrounds

DONT:
- Every card
- Random placement
- High opacity

---

## Role Design

### Nasabah
- Green, warm, friendly
- Hero CTA prominent
- Action-first
- Encouraging language

### Pengelola
- Green but operational
- Data-dense
- Tables prominent

### Admin
- Professional
- System configuration
- User management

### Kades
- Authority
- Summary dashboards
- Village metrics

---

## Responsive Breakpoints

| Name | Width | Devices |
|------|-------|---------|
| Mobile | <= 768px | HP & tablet kecil, sidebar jadi drawer |
| Tablet | 769-1200px | Tablet & laptop kecil, sidebar jadi drawer |
| Desktop | > 1200px | Laptop & monitor, sidebar permanen |

Grid balance: 3 kolom (desktop) → 2 kolom dengan kartu utama full
(<=1200px) → 1 kolom (<=768px). Grid konten: 1fr + 348px → 1 kolom
(<=1200px). Tabel meluber horizontal di dalam `.table-wrap`.

---

## Accessibility

- Contrast: 4.5:1 minimum (body & teks kecil), 3:1 minimum untuk ikon dekoratif
- Teks sekunder 13px memakai `--ink-3` (#5F6B60, 5.59:1) — bukan `--ink-4`
- Teks putih di atas hijau sidebar/kartu memakai alpha >= 0.95
- Touch targets: 44x44px minimum (link navigasi 46px, tombol hamburger 46px)
- Focus states: visible, `outline: 2px solid #2E7D32`
- Ikon dekoratif `aria-hidden="true"`; tabel punya `<caption>` screen-reader
- Drawer: `aria-expanded`, `aria-controls`, tutup dengan Escape
- `prefers-reduced-motion` mematikan seluruh transisi

---

## Do and Dont

### DO
- Fresh Leaf for primary CTA
- Warm Paper for background
- Intentional leaf motif
- Mobile-first
- Strong typography hierarchy
- Clear next actions
- Consistent badges

### DONT
- Green on every card
- Random decorations
- Glassmorphism
- Crowded dashboard
- Bare status colors
- Ignore touch targets
- Emoji sebagai pengganti ikon
- Teks putih transparan di atas hijau dengan alpha < 0.9

---

Version 2.1 - Go Green Village (Dashboard Nasabah: Modern Minimal)
Bank Sampah Digital Desa
Building Green Culture Together
