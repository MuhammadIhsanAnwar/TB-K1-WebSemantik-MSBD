-- Dump database MedanVisit dari PostgreSQL lokal (6 Nov 2026). Jalankan 00_role.sql lebih dulu.
--
-- PostgreSQL database dump
--

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: fn_buka_pada(integer, time without time zone); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_buka_pada(p_id_tempat integer, p_jam time without time zone DEFAULT ((now() AT TIME ZONE 'Asia/Jakarta'::text))::time without time zone) RETURNS boolean
    LANGUAGE sql STABLE
    AS $$
    SELECT COALESCE(p_jam >= jam_buka AND p_jam < jam_tutup, false)
    FROM tempat WHERE id_tempat = p_id_tempat
$$;


--
-- Name: fn_jarak_meter(double precision, double precision, double precision, double precision); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_jarak_meter(lat1 double precision, lng1 double precision, lat2 double precision, lng2 double precision) RETURNS integer
    LANGUAGE sql IMMUTABLE
    AS $$
    SELECT round(2 * 6371000 * asin(sqrt(
        power(sin(radians(lat2 - lat1) / 2), 2)
        + cos(radians(lat1)) * cos(radians(lat2)) * power(sin(radians(lng2 - lng1) / 2), 2)
    )))::integer
$$;


--
-- Name: fn_tempat_terdekat(double precision, double precision, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_tempat_terdekat(p_lat double precision, p_lng double precision, p_batas_m integer DEFAULT 1000) RETURNS TABLE(id_tempat integer, slug character varying, nama_tempat character varying, kategori character varying, jarak_m integer)
    LANGUAGE sql STABLE
    AS $$
    SELECT x.id_tempat, x.slug, x.nama_tempat, x.nama_kategori, x.jarak_m
    FROM (
        SELECT t.id_tempat, t.slug, t.nama_tempat, k.nama_kategori,
               fn_jarak_meter(p_lat, p_lng, t.latitude::float8, t.longitude::float8) AS jarak_m
        FROM tempat t JOIN kategori k ON k.id_kategori = t.id_kategori
    ) x
    WHERE x.jarak_m <= p_batas_m
    ORDER BY x.jarak_m
$$;


--
-- Name: sp_hitung_relasi_dekat(integer); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.sp_hitung_relasi_dekat(IN p_batas_m integer DEFAULT 300)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO relasi (subjek, predikat, objek, sumber)
    SELECT a.slug, 'dekatDengan', b.slug, 'Dihitung dari koordinat'
    FROM tempat a
    JOIN tempat b ON a.id_tempat < b.id_tempat
    WHERE fn_jarak_meter(a.latitude::float8, a.longitude::float8,
                         b.latitude::float8, b.longitude::float8) <= p_batas_m
      AND NOT EXISTS (
          SELECT 1 FROM relasi r
          WHERE r.predikat = 'dekatDengan'
            AND ((r.subjek = a.slug AND r.objek = b.slug) OR (r.subjek = b.slug AND r.objek = a.slug))
      );
END $$;


--
-- Name: trg_relasi_cek_simpul(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_relasi_cek_simpul() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    a tempat%ROWTYPE;
    b tempat%ROWTYPE;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM v_simpul WHERE slug = NEW.subjek) THEN
        RAISE EXCEPTION 'Subjek "%" tidak ada di tempat, entitas, maupun kecamatan', NEW.subjek;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM v_simpul WHERE slug = NEW.objek) THEN
        RAISE EXCEPTION 'Objek "%" tidak ada di tempat, entitas, maupun kecamatan', NEW.objek;
    END IF;
    IF NEW.predikat = 'dekatDengan' THEN
        SELECT * INTO a FROM tempat WHERE slug = NEW.subjek;
        SELECT * INTO b FROM tempat WHERE slug = NEW.objek;
        IF a.id_tempat IS NULL OR b.id_tempat IS NULL THEN
            RAISE EXCEPTION 'Relasi dekatDengan hanya boleh antara dua tempat';
        END IF;
        NEW.jarak_m := fn_jarak_meter(a.latitude::float8, a.longitude::float8,
                                      b.latitude::float8, b.longitude::float8);
    END IF;
    RETURN NEW;
END $$;


--
-- Name: trg_set_updated_at(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_set_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at := now();
    RETURN NEW;
END $$;


--
-- Name: trg_simpul_jaga_relasi(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_simpul_jaga_relasi() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        IF EXISTS (SELECT 1 FROM relasi WHERE subjek = OLD.slug OR objek = OLD.slug) THEN
            RAISE EXCEPTION '"%" masih dipakai di tabel relasi; hapus relasinya dulu', OLD.slug;
        END IF;
        RETURN OLD;
    END IF;
    IF NEW.slug <> OLD.slug THEN
        UPDATE relasi SET subjek = NEW.slug WHERE subjek = OLD.slug;
        UPDATE relasi SET objek  = NEW.slug WHERE objek  = OLD.slug;
    END IF;
    RETURN NEW;
END $$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cara_menuju; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cara_menuju (
    id_cara integer NOT NULL,
    id_tempat integer NOT NULL,
    urutan smallint NOT NULL,
    keterangan character varying(200) NOT NULL
);


--
-- Name: cara_menuju_id_cara_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cara_menuju_id_cara_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cara_menuju_id_cara_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cara_menuju_id_cara_seq OWNED BY public.cara_menuju.id_cara;


--
-- Name: entitas; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.entitas (
    id_entitas integer NOT NULL,
    slug character varying(80) NOT NULL,
    jenis character varying(20) NOT NULL,
    nama character varying(120) NOT NULL,
    ringkas character varying(300),
    masa character varying(60),
    image character varying(160),
    CONSTRAINT ck_entitas_jenis CHECK (((jenis)::text = ANY ((ARRAY['tokoh'::character varying, 'kerajaan'::character varying, 'peristiwa'::character varying, 'budaya'::character varying, 'kuliner'::character varying, 'transportasi'::character varying])::text[])))
);


--
-- Name: entitas_id_entitas_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.entitas_id_entitas_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: entitas_id_entitas_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.entitas_id_entitas_seq OWNED BY public.entitas.id_entitas;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fasilitas_tempat; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fasilitas_tempat (
    id_fasilitas integer NOT NULL,
    id_tempat integer NOT NULL,
    urutan smallint NOT NULL,
    keterangan character varying(160) NOT NULL
);


--
-- Name: fasilitas_tempat_id_fasilitas_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.fasilitas_tempat_id_fasilitas_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: fasilitas_tempat_id_fasilitas_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.fasilitas_tempat_id_fasilitas_seq OWNED BY public.fasilitas_tempat.id_fasilitas;


--
-- Name: jalur; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jalur (
    id_jalur smallint NOT NULL,
    slug character varying(80) NOT NULL,
    nama_jalur character varying(120) NOT NULL,
    ringkas character varying(300) NOT NULL,
    image character varying(160)
);


--
-- Name: jalur_id_jalur_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jalur_id_jalur_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jalur_id_jalur_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jalur_id_jalur_seq OWNED BY public.jalur.id_jalur;


--
-- Name: jalur_singgah; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jalur_singgah (
    id_jalur smallint NOT NULL,
    id_tempat integer NOT NULL,
    urutan smallint NOT NULL,
    CONSTRAINT ck_jalur_urutan CHECK ((urutan >= 1))
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: kategori; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kategori (
    id_kategori smallint NOT NULL,
    kode character varying(20) NOT NULL,
    nama_kategori character varying(60) NOT NULL
);


--
-- Name: kategori_id_kategori_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kategori_id_kategori_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kategori_id_kategori_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kategori_id_kategori_seq OWNED BY public.kategori.id_kategori;


--
-- Name: kecamatan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.kecamatan (
    id_kecamatan smallint NOT NULL,
    slug character varying(60) NOT NULL,
    nama_kecamatan character varying(60) NOT NULL,
    kota character varying(40) DEFAULT 'Medan'::character varying NOT NULL
);


--
-- Name: kecamatan_id_kecamatan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.kecamatan_id_kecamatan_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: kecamatan_id_kecamatan_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.kecamatan_id_kecamatan_seq OWNED BY public.kecamatan.id_kecamatan;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: predikat; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.predikat (
    kode character varying(30) NOT NULL,
    label character varying(40) NOT NULL,
    label_balik character varying(40) NOT NULL,
    pola_maju character varying(160) NOT NULL,
    pola_balik character varying(160) NOT NULL,
    simetris boolean DEFAULT false NOT NULL
);


--
-- Name: relasi; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.relasi (
    id_relasi integer NOT NULL,
    subjek character varying(80) NOT NULL,
    predikat character varying(30) NOT NULL,
    objek character varying(80) NOT NULL,
    jarak_m integer,
    sumber text NOT NULL,
    CONSTRAINT ck_relasi_beda CHECK (((subjek)::text <> (objek)::text))
);


--
-- Name: relasi_id_relasi_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.relasi_id_relasi_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: relasi_id_relasi_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.relasi_id_relasi_seq OWNED BY public.relasi.id_relasi;


--
-- Name: tempat; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tempat (
    id_tempat integer NOT NULL,
    id_kategori smallint NOT NULL,
    id_kecamatan smallint NOT NULL,
    slug character varying(80) NOT NULL,
    nama_tempat character varying(120) NOT NULL,
    ringkas character varying(300) NOT NULL,
    deskripsi text NOT NULL,
    kelurahan character varying(60),
    alamat character varying(200) NOT NULL,
    latitude numeric(9,6) NOT NULL,
    longitude numeric(9,6) NOT NULL,
    koordinat_perkiraan boolean DEFAULT false NOT NULL,
    keterangan_jam character varying(160) NOT NULL,
    jam_buka time(0) without time zone,
    jam_tutup time(0) without time zone,
    jam_contoh boolean DEFAULT true NOT NULL,
    harga_contoh boolean DEFAULT false NOT NULL,
    radius_checkin smallint DEFAULT '100'::smallint NOT NULL,
    durasi_singgah smallint DEFAULT '45'::smallint NOT NULL,
    image character varying(160),
    wikidata character varying(20),
    status character varying(12) DEFAULT 'aktif'::character varying NOT NULL,
    source_url text,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    CONSTRAINT ck_tempat_jam CHECK (((jam_buka IS NULL) OR (jam_tutup IS NULL) OR (jam_tutup > jam_buka))),
    CONSTRAINT ck_tempat_latitude CHECK (((latitude >= ('-90'::integer)::numeric) AND (latitude <= (90)::numeric))),
    CONSTRAINT ck_tempat_longitude CHECK (((longitude >= ('-180'::integer)::numeric) AND (longitude <= (180)::numeric))),
    CONSTRAINT ck_tempat_radius CHECK (((radius_checkin >= 30) AND (radius_checkin <= 300))),
    CONSTRAINT ck_tempat_singgah CHECK (((durasi_singgah >= 5) AND (durasi_singgah <= 480))),
    CONSTRAINT ck_tempat_status CHECK (((status)::text = ANY ((ARRAY['aktif'::character varying, 'sebagian'::character varying, 'tutup'::character varying])::text[])))
);


--
-- Name: tempat_id_tempat_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tempat_id_tempat_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tempat_id_tempat_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tempat_id_tempat_seq OWNED BY public.tempat.id_tempat;


--
-- Name: tiket; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tiket (
    id_tiket integer NOT NULL,
    id_tempat integer NOT NULL,
    jenis_pengunjung character varying(60) NOT NULL,
    harga integer NOT NULL,
    CONSTRAINT ck_tiket_harga CHECK ((harga >= 0))
);


--
-- Name: tiket_id_tiket_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tiket_id_tiket_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tiket_id_tiket_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tiket_id_tiket_seq OWNED BY public.tiket.id_tiket;


--
-- Name: v_simpul; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_simpul AS
 SELECT tempat.slug,
    (tempat.nama_tempat)::character varying AS nama,
    'tempat'::character varying AS jenis
   FROM public.tempat
UNION ALL
 SELECT entitas.slug,
    (entitas.nama)::character varying AS nama,
    (entitas.jenis)::character varying AS jenis
   FROM public.entitas
UNION ALL
 SELECT kecamatan.slug,
    (kecamatan.nama_kecamatan)::character varying AS nama,
    'wilayah'::character varying AS jenis
   FROM public.kecamatan;


--
-- Name: v_relasi_kalimat; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_relasi_kalimat AS
 SELECT r.id_relasi,
    r.subjek,
    s.nama AS nama_subjek,
    r.predikat,
    p.label,
    r.objek,
    o.nama AS nama_objek,
    r.jarak_m,
    replace(replace(replace((p.pola_maju)::text, '{a}'::text, (s.nama)::text), '{b}'::text, (o.nama)::text), '{jarak}'::text, COALESCE((r.jarak_m)::text, ''::text)) AS kalimat,
    r.sumber
   FROM (((public.relasi r
     JOIN public.predikat p ON (((p.kode)::text = (r.predikat)::text)))
     JOIN public.v_simpul s ON (((s.slug)::text = (r.subjek)::text)))
     JOIN public.v_simpul o ON (((o.slug)::text = (r.objek)::text)));


--
-- Name: v_statistik_kategori; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_statistik_kategori AS
 SELECT k.kode,
    k.nama_kategori,
    count(DISTINCT t.id_tempat) AS jumlah_tempat,
    count(DISTINCT tk.id_tempat) AS jumlah_bertiket
   FROM ((public.kategori k
     LEFT JOIN public.tempat t ON ((t.id_kategori = k.id_kategori)))
     LEFT JOIN public.tiket tk ON ((tk.id_tempat = t.id_tempat)))
  GROUP BY k.id_kategori, k.kode, k.nama_kategori;


--
-- Name: v_tempat_lengkap; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_tempat_lengkap AS
 SELECT t.id_tempat,
    t.slug,
    t.nama_tempat,
    k.kode AS kode_kategori,
    k.nama_kategori,
    c.nama_kecamatan,
    t.kelurahan,
    t.alamat,
    t.latitude,
    t.longitude,
    t.keterangan_jam,
    t.jam_buka,
    t.jam_tutup,
    h.harga_termurah,
    COALESCE(h.jumlah_jenis_tiket, (0)::bigint) AS jumlah_jenis_tiket,
    (h.harga_termurah IS NOT NULL) AS bertiket,
    t.radius_checkin,
    t.durasi_singgah,
    t.image,
    t.wikidata,
    t.status
   FROM (((public.tempat t
     JOIN public.kategori k ON ((k.id_kategori = t.id_kategori)))
     JOIN public.kecamatan c ON ((c.id_kecamatan = t.id_kecamatan)))
     LEFT JOIN ( SELECT tiket.id_tempat,
            min(tiket.harga) AS harga_termurah,
            count(*) AS jumlah_jenis_tiket
           FROM public.tiket
          GROUP BY tiket.id_tempat) h ON ((h.id_tempat = t.id_tempat)));


--
-- Name: cara_menuju id_cara; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cara_menuju ALTER COLUMN id_cara SET DEFAULT nextval('public.cara_menuju_id_cara_seq'::regclass);


--
-- Name: entitas id_entitas; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entitas ALTER COLUMN id_entitas SET DEFAULT nextval('public.entitas_id_entitas_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fasilitas_tempat id_fasilitas; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fasilitas_tempat ALTER COLUMN id_fasilitas SET DEFAULT nextval('public.fasilitas_tempat_id_fasilitas_seq'::regclass);


--
-- Name: jalur id_jalur; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jalur ALTER COLUMN id_jalur SET DEFAULT nextval('public.jalur_id_jalur_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: kategori id_kategori; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kategori ALTER COLUMN id_kategori SET DEFAULT nextval('public.kategori_id_kategori_seq'::regclass);


--
-- Name: kecamatan id_kecamatan; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kecamatan ALTER COLUMN id_kecamatan SET DEFAULT nextval('public.kecamatan_id_kecamatan_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: relasi id_relasi; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.relasi ALTER COLUMN id_relasi SET DEFAULT nextval('public.relasi_id_relasi_seq'::regclass);


--
-- Name: tempat id_tempat; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tempat ALTER COLUMN id_tempat SET DEFAULT nextval('public.tempat_id_tempat_seq'::regclass);


--
-- Name: tiket id_tiket; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tiket ALTER COLUMN id_tiket SET DEFAULT nextval('public.tiket_id_tiket_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: cara_menuju; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.cara_menuju (id_cara, id_tempat, urutan, keterangan) VALUES
	(1, 1, 1, 'Becak motor dari Kesawan, tawar ongkos sebelum naik (sekitar Rp20.000–30.000)'),
	(2, 1, 2, 'Bus Listrik Medan koridor K1 melintas di depan istana'),
	(3, 1, 3, 'Jalan kaki dari Masjid Raya Al-Mashun, sekitar 6 menit'),
	(4, 2, 1, 'Jalan kaki dari Istana Maimun, sekitar 6 menit'),
	(5, 3, 1, 'Jalan kaki dari Lapangan Merdeka, sekitar 8 menit'),
	(6, 3, 2, 'Becak motor dari Istana Maimun, sekitar 10 menit'),
	(7, 4, 1, 'Di halaman Istana Maimun'),
	(8, 5, 1, 'Becak motor dari Lapangan Merdeka, sekitar 10 menit'),
	(9, 6, 1, 'Becak motor dari Kampung Madras, sekitar 5 menit'),
	(10, 7, 1, 'Taksi atau ojek daring dari pusat kota, sekitar 30 menit'),
	(11, 8, 1, 'Bus Listrik Medan koridor K1, turun di halte Simpang HM Joni lalu jalan kaki'),
	(12, 8, 2, 'Becak motor dari Masjid Raya Al-Mashun'),
	(13, 9, 1, 'Jalan kaki dari Tjong A Fie Mansion'),
	(14, 10, 1, 'Becak motor dari Kuil Shri Mariamman, sekitar 5 menit'),
	(15, 11, 1, 'Stasiun Medan ada di sisi timur lapangan'),
	(16, 11, 2, 'Halte Bus Listrik Lapangan Merdeka, titik transit koridor K3, K4, dan K5'),
	(17, 12, 1, 'Di seberang Masjid Raya Al-Mashun'),
	(18, 13, 1, 'Jalan kaki dari Kuil Shri Mariamman, sekitar 8 menit');


--
-- Data for Name: entitas; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.entitas (id_entitas, slug, jenis, nama, ringkas, masa, image) VALUES
	(1, 'sultan-maamun-al-rasyid', 'tokoh', 'Sultan Ma''moen Al Rasyid', 'Sultan Deli kesembilan, memerintah 1873–1924.', '1873–1924 (masa pemerintahan)', NULL),
	(2, 'tjong-a-fie', 'tokoh', 'Tjong A Fie', 'Saudagar dan pemimpin komunitas Tionghoa Medan, 1860–1921.', '1860–1921', 'tjong-a-fie-arsip.jpg'),
	(3, 'jacob-nienhuys', 'tokoh', 'Jacob Nienhuys', 'Pengusaha Belanda yang membuka perkebunan tembakau Deli pada 1863.', '1836–1928', NULL),
	(4, 'kesultanan-deli', 'kerajaan', 'Kesultanan Deli', 'Kerajaan Melayu yang berpusat di Medan.', NULL, NULL),
	(5, 'pembangunan-istana-maimun', 'peristiwa', 'Pembangunan Istana Maimun', 'Istana dibangun 1888 dan rampung 1891.', '1888–1891', NULL),
	(6, 'pembangunan-masjid-raya', 'peristiwa', 'Pembangunan Masjid Raya', 'Masjid dibangun 1906 dan rampung 1909.', '1906–1909', NULL),
	(7, 'era-tembakau-deli', 'peristiwa', 'Era Tembakau Deli', 'Masa perkebunan tembakau yang membuat Medan tumbuh menjadi kota.', 'sejak 1863', 'tembakau-deli-1897.jpg'),
	(8, 'legenda-putri-hijau', 'peristiwa', 'Legenda Putri Hijau', 'Cerita rakyat Deli tentang putri, naga, dan meriam yang patah.', 'cerita rakyat', NULL),
	(9, 'melayu-deli', 'budaya', 'Melayu Deli', 'Budaya Melayu pesisir timur Sumatera, budaya Kesultanan Deli.', NULL, NULL),
	(10, 'tionghoa', 'budaya', 'Tionghoa Medan', 'Komunitas Tionghoa yang datang pada masa perkebunan.', NULL, NULL),
	(11, 'tamil', 'budaya', 'Tamil', 'Komunitas Tamil Medan, berpusat di Kampung Madras.', NULL, NULL),
	(12, 'arsitektur-mughal', 'budaya', 'Arsitektur Mughal', 'Gaya bangunan Islam India: kubah, lengkung, dan menara.', NULL, NULL),
	(13, 'bubur-pedas', 'kuliner', 'Bubur pedas', 'Bubur rempah Melayu Deli, biasa disajikan saat Ramadan.', NULL, 'bubur-pedas.jpg'),
	(14, 'roti-jala', 'kuliner', 'Roti jala', 'Roti berbentuk jaring, dimakan dengan kari.', NULL, 'roti-jala.jpg'),
	(15, 'bika-ambon', 'kuliner', 'Bika ambon', 'Kue bersarang beraroma pandan, oleh-oleh khas Medan.', NULL, 'bika-ambon.jpg'),
	(16, 'soto-medan', 'kuliner', 'Soto Medan', 'Soto berkuah santan kuning.', NULL, 'soto-medan.jpg'),
	(17, 'becak-motor', 'transportasi', 'Becak motor', 'Sepeda motor bergandeng kabin, angkutan khas Medan.', NULL, 'becak-medan.jpg'),
	(18, 'bus-listrik-medan', 'transportasi', 'Bus Listrik Medan', 'Bus kota Dishub Kota Medan, pengganti Trans Metro Deli sejak 24 November 2024. Tarif Rp5.000 (pelajar, lansia, disabilitas Rp3.000), bayar nontunai.', NULL, NULL);


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: fasilitas_tempat; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.fasilitas_tempat (id_fasilitas, id_tempat, urutan, keterangan) VALUES
	(1, 1, 1, 'Parkir'),
	(2, 1, 2, 'Toilet'),
	(3, 1, 3, 'Mushola'),
	(4, 1, 4, 'Sewa baju adat Melayu (Rp20.000–50.000)'),
	(5, 1, 5, 'Balita di bawah 5 tahun gratis'),
	(6, 2, 1, 'Parkir'),
	(7, 2, 2, 'Tempat wudu'),
	(8, 2, 3, 'Peminjaman kain penutup'),
	(9, 3, 1, 'Pemandu'),
	(10, 3, 2, 'Toilet'),
	(11, 3, 3, 'Kafe'),
	(12, 5, 1, 'Lepas alas kaki di gerbang'),
	(13, 6, 1, 'Parkir'),
	(14, 6, 2, 'Wajib berpakaian rapi dan tertutup'),
	(15, 7, 1, 'Parkir'),
	(16, 7, 2, 'Toilet'),
	(17, 8, 1, 'Parkir'),
	(18, 8, 2, 'Toilet'),
	(19, 28, 1, 'Lepas alas kaki di gerbang');


--
-- Data for Name: jalur; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.jalur (id_jalur, slug, nama_jalur, ringkas, image) VALUES
	(1, 'jejak-kesultanan-deli', 'Jejak Kesultanan Deli', 'Istana, meriam, masjid, dan taman yang dibangun di masa Sultan Ma''moen Al Rasyid.', 'istana-maimun-1900.jpg'),
	(2, 'satu-kota-banyak-rumah-ibadah', 'Satu kota, banyak rumah ibadah', 'Masjid, kuil, vihara, dan gereja yang berdiri berdekatan di pusat Medan.', 'vihara-gunung-timur.jpg'),
	(3, 'kesawan-tempo-dulu', 'Kesawan tempo dulu', 'Esplanade, rumah saudagar, dan warung soto di jalan dagang tertua Medan.', 'kesawan-arsip.jpg');


--
-- Data for Name: jalur_singgah; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.jalur_singgah (id_jalur, id_tempat, urutan) VALUES
	(1, 1, 1),
	(1, 4, 2),
	(1, 2, 3),
	(1, 12, 4),
	(2, 2, 1),
	(2, 6, 2),
	(2, 5, 3),
	(2, 13, 4),
	(3, 11, 1),
	(3, 3, 2),
	(3, 9, 3);


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: kategori; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.kategori (id_kategori, kode, nama_kategori) VALUES
	(1, 'sejarah', 'Sejarah & cagar budaya'),
	(2, 'museum', 'Museum'),
	(3, 'religi', 'Rumah ibadah'),
	(4, 'kuliner', 'Kuliner'),
	(5, 'ruang-publik', 'Taman & ruang publik'),
	(6, 'belanja', 'Belanja'),
	(7, 'rekreasi-air', 'Rekreasi air');


--
-- Data for Name: kecamatan; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.kecamatan (id_kecamatan, slug, nama_kecamatan, kota) VALUES
	(1, 'kec-medan-maimun', 'Medan Maimun', 'Medan'),
	(2, 'kec-medan-kota', 'Medan Kota', 'Medan'),
	(3, 'kec-medan-barat', 'Medan Barat', 'Medan'),
	(4, 'kec-medan-petisah', 'Medan Petisah', 'Medan'),
	(5, 'kec-medan-polonia', 'Medan Polonia', 'Medan'),
	(6, 'kec-medan-tuntungan', 'Medan Tuntungan', 'Medan'),
	(7, 'kec-medan-baru', 'Medan Baru', 'Medan'),
	(8, 'kec-medan-marelan', 'Medan Marelan', 'Medan'),
	(9, 'kec-medan-timur', 'Medan Timur', 'Medan'),
	(10, 'kec-medan-sunggal', 'Medan Sunggal', 'Medan'),
	(11, 'kec-medan-johor', 'Medan Johor', 'Medan'),
	(12, 'kec-medan-selayang', 'Medan Selayang', 'Medan'),
	(13, 'kec-medan-helvetia', 'Medan Helvetia', 'Medan'),
	(14, 'kec-medan-area', 'Medan Area', 'Medan');


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.migrations (id, migration, batch) VALUES
	(9, '0001_01_01_000000_create_users_table', 1),
	(10, '0001_01_01_000001_create_cache_table', 1),
	(11, '0001_01_01_000002_create_jobs_table', 1),
	(12, '2026_11_06_000100_buat_tabel_katalog', 1),
	(13, '2026_11_06_000200_buat_objek_katalog', 1),
	(14, '2026_11_06_000300_buat_tabel_transaksi', 1),
	(15, '2026_11_06_000400_buat_objek_transaksi', 1),
	(16, '2026_11_06_000500_buat_role_database', 1);


--
-- Data for Name: predikat; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.predikat (kode, label, label_balik, pola_maju, pola_balik, simetris) VALUES
	('dibangunOleh', 'dibangun oleh', 'membangun', '{a} dibangun oleh {b}.', '{a} membangun {b}.', false),
	('memimpin', 'memimpin', 'dipimpin oleh', '{a} memimpin {b}.', '{a} dipimpin oleh {b}.', false),
	('terlibatDalam', 'terlibat dalam', 'melibatkan', '{a} hidup dan berperan pada masa {b}.', 'Pada masa {a}, {b} ikut berperan.', false),
	('merepresentasikan', 'mencerminkan', 'tercermin di', '{a} mencerminkan budaya {b}.', 'Budaya {a} tercermin di {b}.', false),
	('khasDari', 'khas dari', 'punya kuliner khas', '{a} adalah kuliner khas {b}.', 'Salah satu kuliner khas {a} adalah {b}.', false),
	('dekatDengan', 'dekat dengan', 'dekat dengan', '{a} hanya {jarak} meter dari {b}.', '{a} hanya {jarak} meter dari {b}.', true),
	('bergaya', 'bergaya', 'terlihat di', '{a} memakai gaya {b}.', 'Gaya {a} juga terlihat di {b}.', false),
	('bagianDari', 'bagian dari', 'mencakup', '{a} berada di dalam kompleks {b}.', 'Di dalam kompleks {a} ada {b}.', false),
	('berkaitanDengan', 'berkaitan dengan', 'berkaitan dengan', '{a} berkaitan dengan {b}.', '{a} berkaitan dengan {b}.', true),
	('memilikiSejarah', 'punya sejarah', 'melahirkan', 'Sejarah {a} dimulai dari {b}.', '{a} melahirkan {b}.', false),
	('menjual', 'menjual', 'dijual di', '{a} menjual {b}.', '{a} bisa dicicipi di {b}.', false),
	('dilayaniOleh', 'dilayani oleh', 'mengantar ke', '{a} bisa dicapai dengan {b}.', '{a} biasa mengantar pengunjung ke {b}.', false),
	('beradaDi', 'berada di', 'menjadi tempat', '{a} berada di Kecamatan {b}.', 'Kecamatan {a} menjadi tempat {b}.', false);


--
-- Data for Name: relasi; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.relasi (id_relasi, subjek, predikat, objek, jarak_m, sumber) VALUES
	(1, 'istana-maimun', 'dibangunOleh', 'sultan-maamun-al-rasyid', NULL, 'https://id.wikipedia.org/wiki/Istana_Maimun'),
	(2, 'masjid-raya-al-mashun', 'dibangunOleh', 'sultan-maamun-al-rasyid', NULL, 'https://id.wikipedia.org/wiki/Masjid_Raya_Medan'),
	(3, 'sultan-maamun-al-rasyid', 'memimpin', 'kesultanan-deli', NULL, 'https://id.wikipedia.org/wiki/Kesultanan_Deli'),
	(4, 'istana-maimun', 'merepresentasikan', 'melayu-deli', NULL, 'https://id.wikipedia.org/wiki/Istana_Maimun'),
	(5, 'istana-maimun', 'bergaya', 'arsitektur-mughal', NULL, 'https://id.wikipedia.org/wiki/Istana_Maimun'),
	(6, 'masjid-raya-al-mashun', 'bergaya', 'arsitektur-mughal', NULL, 'https://id.wikipedia.org/wiki/Masjid_Raya_Medan'),
	(7, 'istana-maimun', 'dekatDengan', 'masjid-raya-al-mashun', 361, 'Dihitung dari koordinat'),
	(8, 'istana-maimun', 'memilikiSejarah', 'pembangunan-istana-maimun', NULL, 'https://id.wikipedia.org/wiki/Istana_Maimun'),
	(9, 'masjid-raya-al-mashun', 'memilikiSejarah', 'pembangunan-masjid-raya', NULL, 'https://id.wikipedia.org/wiki/Masjid_Raya_Medan'),
	(10, 'meriam-puntung', 'bagianDari', 'istana-maimun', NULL, 'https://id.wikipedia.org/wiki/Meriam_Puntung'),
	(11, 'meriam-puntung', 'berkaitanDengan', 'legenda-putri-hijau', NULL, 'https://id.wikipedia.org/wiki/Putri_Hijau'),
	(12, 'bubur-pedas', 'khasDari', 'melayu-deli', NULL, 'https://id.wikipedia.org/wiki/Bubur_pedas'),
	(13, 'roti-jala', 'khasDari', 'melayu-deli', NULL, 'https://id.wikipedia.org/wiki/Roti_jala'),
	(14, 'sultan-maamun-al-rasyid', 'terlibatDalam', 'era-tembakau-deli', NULL, 'https://id.wikipedia.org/wiki/Kesultanan_Deli'),
	(15, 'tjong-a-fie', 'terlibatDalam', 'era-tembakau-deli', NULL, 'https://id.wikipedia.org/wiki/Tjong_A_Fie'),
	(16, 'jacob-nienhuys', 'terlibatDalam', 'era-tembakau-deli', NULL, 'https://id.wikipedia.org/wiki/Tembakau_Deli'),
	(17, 'tjong-a-fie-mansion', 'dibangunOleh', 'tjong-a-fie', NULL, 'https://id.wikipedia.org/wiki/Rumah_Tjong_A_Fie'),
	(18, 'tjong-a-fie', 'berkaitanDengan', 'tionghoa', NULL, 'https://id.wikipedia.org/wiki/Tjong_A_Fie'),
	(19, 'vihara-gunung-timur', 'merepresentasikan', 'tionghoa', NULL, 'https://id.wikipedia.org/wiki/Vihara_Gunung_Timur'),
	(20, 'kuil-shri-mariamman', 'merepresentasikan', 'tamil', NULL, 'https://id.wikipedia.org/wiki/Kuil_Shri_Mariamman'),
	(21, 'graha-maria-annai-velangkanni', 'berkaitanDengan', 'tamil', NULL, 'https://id.wikipedia.org/wiki/Graha_Maria_Annai_Velangkanni'),
	(22, 'soto-kesawan', 'dekatDengan', 'tjong-a-fie-mansion', 52, 'Dihitung dari koordinat'),
	(23, 'soto-kesawan', 'menjual', 'soto-medan', NULL, 'https://koran-jakarta.com/2024-09-17/ada-racikan-yogyakarta-di-soto-kesawan-medan'),
	(24, 'bika-ambon-mojopahit', 'menjual', 'bika-ambon', NULL, 'https://www.indonesia.travel/id-id/travel-ideas/gastronomy/bika-ambonn/'),
	(25, 'istana-maimun', 'dilayaniOleh', 'becak-motor', NULL, 'https://kumparan.com/sumutnews/tips-naik-becak-motor-di-kota-para-ketua-1552205494541583850'),
	(26, 'museum-negeri-sumatera-utara', 'dilayaniOleh', 'bus-listrik-medan', NULL, 'https://id.wikipedia.org/wiki/Bus_Listrik_Medan'),
	(27, 'masjid-lama-gang-bengkok', 'dibangunOleh', 'tjong-a-fie', NULL, 'https://id.wikipedia.org/wiki/Masjid_Lama_Gang_Bengkok'),
	(28, 'balai-kota-lama', 'berkaitanDengan', 'tjong-a-fie', NULL, 'https://www.detik.com/sumut/berita/d-7045682/sejarah-balai-kota-medan-ada-peran-miliarder-tjong-a-fie'),
	(29, 'gedung-avros', 'berkaitanDengan', 'era-tembakau-deli', NULL, 'https://www.penang-traveltips.com/indonesia/medan/bks-pps-building.htm'),
	(30, 'kuil-nagarattar', 'merepresentasikan', 'tamil', NULL, 'https://id.wikipedia.org/wiki/Kuil_Sree_Soepramaniem_Nagarattar'),
	(31, 'seng-hap-1881', 'merepresentasikan', 'tionghoa', NULL, 'https://rri.co.id/medan/iptek/2772486/seng-hap-1881-dari-bangunan-bersejarah-hingga-ruang-umkm-dan-kuliner'),
	(32, 'tip-top-restaurant', 'dekatDengan', 'tjong-a-fie-mansion', 109, 'Dihitung dari koordinat'),
	(33, 'seng-hap-1881', 'dekatDengan', 'lapangan-merdeka', 212, 'Dihitung dari koordinat'),
	(34, 'katedral-medan', 'dekatDengan', 'istana-maimun', 1037, 'Dihitung dari koordinat'),
	(35, 'taman-sri-deli', 'dekatDengan', 'masjid-raya-al-mashun', 141, 'Dihitung dari koordinat'),
	(36, 'lapangan-merdeka', 'dekatDengan', 'soto-kesawan', 628, 'Dihitung dari koordinat'),
	(37, 'gereja-immanuel', 'dekatDengan', 'kuil-shri-mariamman', 373, 'Dihitung dari koordinat'),
	(38, 'istana-maimun', 'beradaDi', 'kec-medan-maimun', NULL, 'BPS Kota Medan'),
	(39, 'masjid-raya-al-mashun', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(40, 'tjong-a-fie-mansion', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(41, 'meriam-puntung', 'beradaDi', 'kec-medan-maimun', NULL, 'BPS Kota Medan'),
	(42, 'kuil-shri-mariamman', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(43, 'vihara-gunung-timur', 'beradaDi', 'kec-medan-polonia', NULL, 'BPS Kota Medan'),
	(44, 'graha-maria-annai-velangkanni', 'beradaDi', 'kec-medan-tuntungan', NULL, 'BPS Kota Medan'),
	(45, 'museum-negeri-sumatera-utara', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(46, 'soto-kesawan', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(47, 'bika-ambon-mojopahit', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(48, 'lapangan-merdeka', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(49, 'taman-sri-deli', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(50, 'gereja-immanuel', 'beradaDi', 'kec-medan-polonia', NULL, 'BPS Kota Medan');
INSERT INTO public.relasi (id_relasi, subjek, predikat, objek, jarak_m, sumber) VALUES
	(51, 'balai-kota-lama', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(52, 'gedung-lonsum', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(53, 'menara-air-tirtanadi', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(54, 'pos-bloc-medan', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(55, 'gedung-bank-indonesia-lama', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(56, 'gedung-avros', 'beradaDi', 'kec-medan-maimun', NULL, 'BPS Kota Medan'),
	(57, 'gedung-warenhuis', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(58, 'monumen-sisingamangaraja-xii', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(59, 'museum-perjuangan-tni', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(60, 'rahmat-gallery', 'beradaDi', 'kec-medan-baru', NULL, 'BPS Kota Medan'),
	(61, 'museum-situs-kota-cina', 'beradaDi', 'kec-medan-marelan', NULL, 'BPS Kota Medan'),
	(62, 'masjid-lama-gang-bengkok', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(63, 'katedral-medan', 'beradaDi', 'kec-medan-maimun', NULL, 'BPS Kota Medan'),
	(64, 'vihara-setia-budi', 'beradaDi', 'kec-medan-timur', NULL, 'BPS Kota Medan'),
	(65, 'kuil-nagarattar', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(66, 'seng-hap-1881', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(67, 'kuliner-jalan-semarang', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(68, 'kuliner-selat-panjang', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(69, 'kuliner-pagaruyung', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(70, 'kuliner-multatuli', 'beradaDi', 'kec-medan-maimun', NULL, 'BPS Kota Medan'),
	(71, 'bolu-meranti-kruing', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(72, 'ucok-durian', 'beradaDi', 'kec-medan-baru', NULL, 'BPS Kota Medan'),
	(73, 'soto-sinar-pagi', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(74, 'rumah-makan-tabona', 'beradaDi', 'kec-medan-maimun', NULL, 'BPS Kota Medan'),
	(75, 'sate-memeng', 'beradaDi', 'kec-medan-timur', NULL, 'BPS Kota Medan'),
	(76, 'kedai-kopi-apek', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(77, 'tip-top-restaurant', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(78, 'rumah-makan-sipirok', 'beradaDi', 'kec-medan-sunggal', NULL, 'BPS Kota Medan'),
	(79, 'taman-cadika', 'beradaDi', 'kec-medan-johor', NULL, 'BPS Kota Medan'),
	(80, 'taman-ahmad-yani', 'beradaDi', 'kec-medan-maimun', NULL, 'BPS Kota Medan'),
	(81, 'taman-beringin', 'beradaDi', 'kec-medan-polonia', NULL, 'BPS Kota Medan'),
	(82, 'taman-teladan', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(83, 'taman-gajah-mada', 'beradaDi', 'kec-medan-baru', NULL, 'BPS Kota Medan'),
	(84, 'taman-budaya-medan', 'beradaDi', 'kec-medan-timur', NULL, 'BPS Kota Medan'),
	(85, 'avros-park', 'beradaDi', 'kec-medan-polonia', NULL, 'BPS Kota Medan'),
	(86, 'lapangan-benteng', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(87, 'delipark-mall', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan'),
	(88, 'sun-plaza', 'beradaDi', 'kec-medan-polonia', NULL, 'BPS Kota Medan'),
	(89, 'centre-point-mall', 'beradaDi', 'kec-medan-timur', NULL, 'BPS Kota Medan'),
	(90, 'plaza-medan-fair', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(91, 'manhattan-times-square', 'beradaDi', 'kec-medan-sunggal', NULL, 'BPS Kota Medan'),
	(92, 'ringroad-city-walks', 'beradaDi', 'kec-medan-sunggal', NULL, 'BPS Kota Medan'),
	(93, 'cambridge-city-square', 'beradaDi', 'kec-medan-petisah', NULL, 'BPS Kota Medan'),
	(94, 'focal-point', 'beradaDi', 'kec-medan-selayang', NULL, 'BPS Kota Medan'),
	(95, 'plaza-millenium', 'beradaDi', 'kec-medan-helvetia', NULL, 'BPS Kota Medan'),
	(96, 'medan-mall', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(97, 'thamrin-plaza', 'beradaDi', 'kec-medan-area', NULL, 'BPS Kota Medan'),
	(98, 'lippo-plaza-medan', 'beradaDi', 'kec-medan-polonia', NULL, 'BPS Kota Medan'),
	(99, 'suzuya-marelan', 'beradaDi', 'kec-medan-marelan', NULL, 'BPS Kota Medan'),
	(100, 'irian-supermarket', 'beradaDi', 'kec-medan-barat', NULL, 'BPS Kota Medan');
INSERT INTO public.relasi (id_relasi, subjek, predikat, objek, jarak_m, sumber) VALUES
	(101, 'pasar-ramai', 'beradaDi', 'kec-medan-area', NULL, 'BPS Kota Medan'),
	(102, 'kolam-renang-paradiso', 'beradaDi', 'kec-medan-kota', NULL, 'BPS Kota Medan'),
	(103, 'kolam-renang-deli', 'beradaDi', 'kec-medan-timur', NULL, 'BPS Kota Medan'),
	(104, 'kolam-renang-tirta-kartika', 'beradaDi', 'kec-medan-helvetia', NULL, 'BPS Kota Medan'),
	(105, 'bumi-asri-waterpark', 'beradaDi', 'kec-medan-helvetia', NULL, 'BPS Kota Medan'),
	(106, 'kolam-renang-primbana', 'beradaDi', 'kec-medan-selayang', NULL, 'BPS Kota Medan');


--
-- Data for Name: tempat; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tempat (id_tempat, id_kategori, id_kecamatan, slug, nama_tempat, ringkas, deskripsi, kelurahan, alamat, latitude, longitude, koordinat_perkiraan, keterangan_jam, jam_buka, jam_tutup, jam_contoh, harga_contoh, radius_checkin, durasi_singgah, image, wikidata, status, source_url, created_at, updated_at) VALUES
	(1, 1, 1, 'istana-maimun', 'Istana Maimun', 'Istana Kesultanan Deli, dibangun 1888–1891 oleh Sultan Ma''moen Al Rasyid.', 'Istana Maimun dibangun Sultan Ma''moen Al Rasyid Perkasa Alamsyah, sultan Deli kesembilan, dan rampung pada 1891. Bangunannya memadukan unsur Melayu, Islam, India, dan Eropa.

Warna kuning yang mendominasi istana adalah warna kebesaran Melayu. Sampai sekarang sebagian ruangan masih ditinggali kerabat kesultanan, dan pengunjung bisa berfoto memakai baju adat Melayu di balairung.

Di halaman istana ada bangunan kecil yang menyimpan Meriam Puntung, yang dalam cerita rakyat dikaitkan dengan legenda Putri Hijau.', 'Aur', 'Jl. Brigjend Katamso No. 66', 3.575290, 98.684050, false, 'Setiap hari, 08.00–17.00', '08:00:00', '17:00:00', true, true, 150, 60, 'istana-maimun.jpg', 'Q1674833', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(2, 3, 2, 'masjid-raya-al-mashun', 'Masjid Raya Al-Mashun', 'Masjid Kesultanan Deli, dibangun 1906–1909 dengan denah segi delapan.', 'Masjid Raya Al-Mashun dibangun oleh Sultan Ma''moen Al Rasyid, sultan yang sama yang membangun Istana Maimun, dan selesai pada 1909.

Denahnya segi delapan dengan kubah utama dikelilingi empat serambi. Masjid ini masih dipakai untuk salat berjamaah, jadi pengunjung datang di luar waktu salat dan berpakaian sopan.', 'Mesjid', 'Jl. Sisingamangaraja', 3.575170, 98.687300, false, 'Terbuka di luar waktu salat', '05:00:00', '21:00:00', true, false, 100, 45, 'masjid-raya.jpg', 'Q12497331', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(3, 1, 3, 'tjong-a-fie-mansion', 'Tjong A Fie Mansion', 'Rumah saudagar Tjong A Fie di Kesawan, kini museum rumah.', 'Tjong A Fie adalah saudagar Hakka yang datang ke Deli pada masa kejayaan tembakau dan menjadi pemimpin komunitas Tionghoa Medan. Rumahnya di Kesawan selesai dibangun sekitar 1900.

Arsitekturnya memadukan rumah Tionghoa, Eropa, dan Melayu. Kamar, perabot, dan foto keluarga masih tersimpan, sehingga pengunjung bisa melihat bagaimana sebuah keluarga saudagar hidup di Medan awal abad ke-20.', 'Kesawan', 'Jl. Jend. Ahmad Yani No. 105', 3.585430, 98.680370, false, 'Setiap hari, 09.00–17.00 (masuk terakhir 16.30), tutup saat Imlek', '09:00:00', '17:00:00', false, false, 80, 60, 'tjong-a-fie-mansion.jpg', 'Q7810631', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(4, 1, 1, 'meriam-puntung', 'Meriam Puntung', 'Meriam di halaman Istana Maimun yang dikaitkan dengan legenda Putri Hijau.', 'Menurut cerita rakyat, meriam ini adalah penjelmaan saudara Putri Hijau yang menembak tanpa henti saat kerajaan diserang hingga patah. Satu potongannya ada di Istana Maimun, potongan lainnya di Tanah Karo.', 'Aur', 'Kompleks Istana Maimun', 3.574930, 98.684050, false, 'Mengikuti jam buka Istana Maimun', '08:00:00', '17:00:00', true, false, 60, 15, 'meriam-puntung.jpg', NULL, 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(5, 3, 4, 'kuil-shri-mariamman', 'Kuil Shri Mariamman', 'Kuil Hindu tertua di Medan, berdiri sejak 1884 di Kampung Madras.', 'Kuil ini dibangun komunitas Tamil pada 1884 dan menjadi pusat Kampung Madras, kawasan yang juga dikenal sebagai Little India Medan. Menara gerbangnya dipenuhi patung dewa berwarna cerah.', 'Petisah Tengah', 'Jl. Teuku Umar No. 18', 3.583970, 98.671020, false, 'Setiap hari, 06.00–12.00 dan 16.00–20.00', '06:00:00', '20:00:00', true, false, 80, 30, 'kuil-shri-mariamman.jpg', 'Q12492840', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(6, 3, 5, 'vihara-gunung-timur', 'Vihara Gunung Timur', 'Kelenteng Tionghoa di tepi Sungai Babura, dikenal dengan atap naga dan lampionnya.', 'Vihara Gunung Timur adalah salah satu kelenteng terbesar di Medan. Menjelang Imlek, halaman dan atapnya dipenuhi lampion merah.', 'Madras Hulu', 'Jl. Hang Tuah No. 16', 3.578010, 98.668990, false, 'Setiap hari, 09.00–16.00', '09:00:00', '16:00:00', true, false, 80, 30, 'vihara-gunung-timur.jpg', 'Q14933726', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(7, 3, 6, 'graha-maria-annai-velangkanni', 'Graha Maria Annai Velangkanni', 'Gereja Katolik bergaya Indo-Mughal di Tanjung Selamat, diresmikan 2005.', 'Graha ini dibangun untuk Bunda Maria Annai Velangkanni yang dihormati umat Katolik Tamil. Bangunannya bertingkat seperti candi dengan ornamen India, sehingga sering dikira kuil.', 'Tanjung Selamat', 'Jl. Sakura III No. 7–10, Taman Sakura Indah', 3.547630, 98.608700, false, 'Setiap hari, 08.00–18.00; misa Senin–Sabtu pukul 18.00', '08:00:00', '18:00:00', true, false, 120, 45, 'graha-maria-annai-velangkanni.jpg', 'Q4349197', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(8, 2, 2, 'museum-negeri-sumatera-utara', 'Museum Negeri Sumatera Utara', 'Museum sejarah dan budaya etnis-etnis Sumatera Utara.', 'Koleksinya mengenalkan etnis-etnis Sumatera Utara, dari Melayu, Batak, Nias, sampai Tionghoa dan Tamil yang datang pada masa perkebunan.', 'Teladan Barat', 'Jl. H. M. Joni No. 51', 3.568580, 98.696390, false, 'Selasa–Minggu, 09.00–16.00 (Senin tutup)', '09:00:00', '16:00:00', true, true, 100, 60, 'museum-negeri-sumut.jpg', 'Q12499672', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(9, 4, 3, 'soto-kesawan', 'Soto Kesawan', 'Warung soto lama di Kesawan, beberapa langkah dari Tjong A Fie Mansion.', 'Warung ini berjualan sejak 1950-an. Kuahnya berempah dengan sedikit santan dan agak manis. Soto ayam, soto jeroan sapi, dan soto udang sekitar Rp28.000–32.000, sudah termasuk nasi (2024).', 'Kesawan', 'Jl. Jend. Ahmad Yani', 3.584980, 98.680260, false, 'Setiap hari, 08.00–14.30', '08:00:00', '14:00:00', true, false, 60, 40, 'soto-medan.jpg', NULL, 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(10, 4, 4, 'bika-ambon-mojopahit', 'Deretan Bika Ambon Jl. Mojopahit', 'Jalan tempat toko-toko bika ambon berjajar, tujuan oleh-oleh khas Medan.', 'Bika ambon Medan bertekstur bersarang, beraroma daun jeruk dan pandan. Jl. Mojopahit punya sedikitnya 30 toko; Bika Ambon Zulaikha menjual Rp80.000–150.000 per loyang.', 'Petisah Tengah', 'Jl. Mojopahit (Bika Ambon Zulaikha No. 70 A-C)', 3.586180, 98.666170, false, 'Setiap hari, 06.00–21.00', '06:00:00', '21:00:00', true, false, 120, 30, 'bika-ambon.jpg', NULL, 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(11, 5, 3, 'lapangan-merdeka', 'Lapangan Merdeka', 'Alun-alun kota yang di peta 1930 masih bernama Esplanade.', 'Di peta Medan 1930, lapangan ini tercatat sebagai Esplanade, dikelilingi kantor pos, bank, dan hotel kolonial.', 'Kesawan', 'Jl. Balai Kota', 3.590380, 98.678600, false, 'Ruang terbuka, jam resmi belum diumumkan', '06:00:00', '22:00:00', true, false, 150, 30, 'lapangan-merdeka.jpg', 'Q12498196', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(12, 5, 2, 'taman-sri-deli', 'Taman Sri Deli', 'Taman berkolam di depan Masjid Raya Al-Mashun.', 'Taman ini dahulu bagian dari kawasan Kesultanan Deli dan kini jadi tempat warga bersantai sore.', 'Mesjid', 'Jl. Sisingamangaraja / Jl. Mesjid Raya', 3.575800, 98.686200, false, 'Setiap hari, 07.00–21.00', '07:00:00', '21:00:00', true, false, 100, 20, 'taman-sri-deli.jpg', 'Q12518862', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(13, 3, 5, 'gereja-immanuel', 'GPIB Immanuel Medan', 'Gereja Protestan berlonceng menara di Jl. Diponegoro.', 'Salah satu gereja tua Medan yang masih dipakai beribadah.', 'Madras Hulu', 'Jl. Diponegoro No. 25–27', 3.580980, 98.672550, false, 'Di luar jadwal ibadah', '08:00:00', '16:00:00', true, false, 80, 20, 'gereja-immanuel.jpg', 'Q113648754', 'aktif', NULL, '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(14, 1, 3, 'balai-kota-lama', 'Gedung Balai Kota Lama', 'Bekas balai kota Medan di depan Lapangan Merdeka, dibangun 1908–1909, kini bagian kompleks hotel.', 'Gedung bermenara jam ini dibangun pada 1908–1909 sebagai balai kota Medan. Pembangunannya disebut melibatkan peran saudagar Tjong A Fie. Kini gedungnya dikelola swasta bersama hotel di sebelahnya.', 'Kesawan', 'Jl. Balai Kota', 3.590330, 98.677400, false, 'Dilihat dari luar, gedung terintegrasi dengan hotel', NULL, NULL, true, false, 100, 15, NULL, 'Q6805113', 'aktif', 'https://www.detik.com/sumut/berita/d-7045682/sejarah-balai-kota-medan-ada-peran-miliarder-tjong-a-fie', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(15, 1, 3, 'gedung-lonsum', 'Gedung London Sumatra', 'Gedung perkantoran perkebunan di Kesawan, disebut gedung berlift pertama di Sumatera.', 'Dibangun pada 1906 (sebagian sumber menulis 1909) untuk perusahaan perkebunan Harrisons & Crosfield. Sampai sekarang gedung ini masih menjadi kantor PT PP London Sumatra Indonesia.', 'Kesawan', 'Jl. Jend. Ahmad Yani No. 2', 3.588570, 98.678030, false, 'Dilihat dari luar, masih kantor aktif', NULL, NULL, true, false, 100, 10, NULL, 'Q114814144', 'aktif', 'https://economy.okezone.com/read/2023/04/12/470/2797205/ini-gedung-dengan-lift-pertama-di-sumatera-sudah-ada-sejak-zaman-kolonial', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(16, 1, 2, 'menara-air-tirtanadi', 'Menara Air Tirtanadi', 'Menara air peninggalan kolonial yang menjadi penanda kota, selesai 1908.', 'Menara ini selesai pada 1908 untuk menyalurkan air bersih ke kota. Kini berstatus cagar budaya dan menjadi latar foto yang populer.', 'Pasar Baru', 'Jl. Sisingamangaraja, kompleks PDAM Tirtanadi', 3.582100, 98.684990, false, 'Dilihat dari luar, berada di kompleks PDAM', NULL, NULL, true, false, 100, 10, NULL, 'Q19746232', 'aktif', 'https://www.detik.com/sumut/budaya/d-7210677/sejarah-menara-pdam-tirtanadi-medan-dibangun-di-masa-kolonial-belanda', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(17, 1, 3, 'pos-bloc-medan', 'Kantor Pos Besar Medan (Pos Bloc)', 'Kantor pos kolonial di sisi Lapangan Merdeka yang kini sebagian besar menjadi ruang kuliner dan kreatif Pos Bloc.', 'Gedung kantor pos ini berstatus cagar budaya. Sebagian besar ruangnya kini dipakai Pos Bloc untuk tenant kuliner dan acara kreatif, jadi pengunjung bisa masuk melihat bangunannya.', 'Kesawan', 'Jl. Pos No. 1', 3.591940, 98.677430, false, 'Senin–Jumat 10.00–22.00, Sabtu–Minggu 08.00–23.00', '10:00:00', '22:00:00', true, false, 100, 60, NULL, 'Q12489173', 'aktif', 'https://www.detik.com/sumut/berita/d-7187048/sejarah-kantor-pos-medan-yang-kini-jadi-pusat-kuliner-milenial', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(18, 1, 3, 'gedung-bank-indonesia-lama', 'Gedung Bank Indonesia Lama', 'Gedung De Javasche Bank di Jl. Balai Kota, masih dipakai Bank Indonesia.', 'Bangunan bergaya Eropa ini dulu kantor De Javasche Bank dan kini masih menjadi kantor Bank Indonesia, sehingga hanya bisa dinikmati dari luar.', 'Kesawan', 'Jl. Balai Kota No. 4', 3.590690, 98.676610, false, 'Dilihat dari luar, masih kantor Bank Indonesia', NULL, NULL, true, false, 100, 10, NULL, NULL, 'aktif', 'https://en.wikipedia.org/wiki/List_of_colonial_buildings_in_Medan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(19, 1, 1, 'gedung-avros', 'Gedung AVROS (BKS-PPS)', 'Gedung asosiasi pengusaha perkebunan masa kolonial di sudut Jl. Ahmad Yani.', 'Dibangun sekitar 1918–1919 untuk AVROS, perkumpulan pengusaha perkebunan karet Sumatera Timur. Sejak 1967 gedung ini menjadi kantor BKS-PPS.', 'Aur', 'Sudut Jl. Jend. Ahmad Yani dan Jl. Palang Merah', 3.584470, 98.680510, false, 'Dilihat dari luar, masih kantor BKS-PPS', NULL, NULL, true, false, 100, 10, NULL, NULL, 'aktif', 'https://www.penang-traveltips.com/indonesia/medan/bks-pps-building.htm', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(20, 1, 3, 'gedung-warenhuis', 'Gedung Warenhuis', 'Toserba pertama di Medan dari masa kolonial, direvitalisasi dan dibuka lagi sekitar 2025 sebagai pusat seni dan UMKM.', 'Warenhuis dibangun sekitar 1916–1919 sebagai pusat perbelanjaan modern pertama di Medan. Setelah lama terbengkalai, gedungnya direvitalisasi pada 2023–2024 dan diresmikan kembali sekitar Februari 2025.', 'Kesawan', 'Jl. Ahmad Yani VII / Jl. Hindu', 3.588330, 98.676940, true, 'Jam buka belum terverifikasi', NULL, NULL, true, false, 100, 30, NULL, NULL, 'aktif', 'https://fahum.umsu.ac.id/info/gedung-warenhuis-diresmikan/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(21, 1, 2, 'monumen-sisingamangaraja-xii', 'Monumen Sisingamangaraja XII', 'Monumen pahlawan nasional di Teladan Barat. Kondisinya kurang terawat dan pendoponya terbakar Juni 2026.', 'Monumen ini dibangun pada 1979 dan diresmikan 1992. Ini monumen, bukan museum: museum Sisingamangaraja XII berada di Balige. Pendopo rumah adat di kompleksnya terbakar pada 22 Juni 2026.', 'Teladan Barat', 'Jl. Sisingamangaraja No. 98', 3.564980, 98.692920, false, 'Ruang terbuka, jam resmi tidak ada', NULL, NULL, true, false, 100, 15, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/berita/d-8267318/monumen-sisingamangaraja-xii-medan-tak-terawat-begini-penampakannya', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(22, 2, 4, 'museum-perjuangan-tni', 'Museum Perjuangan TNI Bukit Barisan', 'Museum perjuangan Kodam I/Bukit Barisan di gedung kolonial Jl. Zainul Arifin.', 'Museum ini menyimpan senjata, seragam, dan dokumen perjuangan di Sumatera Utara. Dikelola Kodam I/Bukit Barisan dan hanya buka pada hari kerja.', 'Petisah Tengah', 'Jl. K.H. Zainul Arifin No. 8', 3.583920, 98.674140, false, 'Senin–Jumat, 08.00–15.30 (masuk dengan donasi)', '08:00:00', '15:00:00', true, false, 100, 45, NULL, 'Q86584463', 'aktif', 'https://mamikos.com/info/museum-terbaik-di-medan-yang-wajib-dikunjungi-gnr/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(23, 2, 7, 'rahmat-gallery', 'Rahmat International Wildlife Museum & Gallery', 'Museum satwa awetan dengan koleksi taksidermi besar dari berbagai benua.', 'Galeri milik Rahmat Shah ini memajang ratusan satwa awetan dalam diorama habitatnya. Dibuka untuk umum sejak 1990-an.', 'Petisah Hulu', 'Jl. S. Parman No. 309', 3.579260, 98.667730, false, 'Setiap hari, 09.00–17.00', '09:00:00', '17:00:00', true, true, 100, 90, NULL, 'Q18393164', 'aktif', 'https://www.detik.com/sumut/berita/d-8239652/6-museum-di-medan-yang-wajib-dikunjungi', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(24, 2, 8, 'museum-situs-kota-cina', 'Museum Situs Kota Cina', 'Museum situs pelabuhan dagang kuno abad ke-11 sampai 14 di Medan Marelan.', 'Di Kota Cina ditemukan pecahan keramik, arca, dan sisa permukiman pelabuhan dagang yang ramai jauh sebelum kota Medan berdiri. Temuannya dipamerkan di museum kecil di lokasi situs.', 'Paya Pasir', 'Jl. Kota Cina', 3.723240, 98.656750, false, 'Kamis–Minggu, 09.00–17.00', '09:00:00', '17:00:00', true, true, 100, 60, NULL, 'Q19748034', 'aktif', 'https://mamikos.com/info/museum-terbaik-di-medan-yang-wajib-dikunjungi-gnr/?halaman=2', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(25, 3, 3, 'masjid-lama-gang-bengkok', 'Masjid Lama Gang Bengkok', 'Masjid tua di Kesawan yang dibiayai Tjong A Fie, perpaduan gaya Tionghoa, Melayu, dan Persia.', 'Masjid ini dibangun di atas tanah wakaf Datuk Muhammad Ali dengan biaya dari Tjong A Fie, lalu diserahkan kepada Kesultanan Deli. Tahun berdirinya ditulis berbeda-beda, antara 1874 dan 1890.', 'Kesawan', 'Jl. Mesjid No. 62', 3.586680, 98.678010, false, 'Di luar waktu salat', '08:00:00', '17:00:00', true, false, 80, 20, NULL, 'Q110706913', 'aktif', 'https://www.detik.com/sumut/budaya/d-8271055/mengenal-10-masjid-bersejarah-di-medan-dari-arsitektur-hingga-ceritanya', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(26, 3, 1, 'katedral-medan', 'Katedral Medan', 'Gereja Katedral Santa Perawan Maria yang Dikandung Tanpa Noda di Jl. Pemuda.', 'Nama resminya Gereja Katedral Santa Perawan Maria yang Dikandung Tanpa Noda. Gereja pertamanya berdiri 1879, bangunan sekarang dari 1905 dan diperluas pada 1928.', 'Aur', 'Jl. Pemuda No. 1', 3.584250, 98.681450, false, 'Di luar jam misa (Minggu 06.30, 08.00, 10.00, 17.30)', '08:00:00', '17:00:00', true, false, 80, 20, NULL, 'Q16951164', 'aktif', 'https://gcatholic.org/churches/asia/3538.htm', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(27, 3, 9, 'vihara-setia-budi', 'Vihara Setia Budi', 'Kelenteng di Jl. Irian Barat, di lokasi tempat kelenteng Kwan Tee Bio berdiri sejak 1884.', 'Di Jl. Irian Barat pernah berdiri kelenteng Kwan Tee Bio yang digagas Tjong Yong Hian pada 1884. Vihara Setia Budi diduga penerusnya, tetapi belum ada sumber yang menyebut keduanya sekaligus.', 'Gang Buntu', 'Jl. Irian Barat No. 6D', 3.589280, 98.681310, false, 'Jam buka belum terverifikasi', NULL, NULL, true, false, 80, 20, NULL, 'Q138358755', 'aktif', 'https://storiesfromdeli.com/2020/11/27/kwan-tee-bio/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(28, 3, 4, 'kuil-nagarattar', 'Kuil Sree Soepramaniem Nagarattar', 'Kuil Hindu bergaya Dravida milik komunitas Chettiar, selesai 1892.', 'Kuil ini dibangun komunitas Nattukottai Chettiar dengan gaya Dravida dan dikenal juga sebagai Kuil Shri Subramaniam.', 'Petisah Tengah', 'Jl. Kebun Bunga No. 6', 3.587130, 98.669050, false, 'Senin–Sabtu 06.00–11.00 dan 17.00–20.00; Minggu 06.00–09.30 dan 17.00–20.00', '06:00:00', '20:00:00', true, false, 80, 20, NULL, NULL, 'aktif', 'https://id.wikipedia.org/wiki/Kuil_Sree_Soepramaniem_Nagarattar', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(29, 4, 3, 'seng-hap-1881', 'Seng Hap 1881', 'Gedung niaga tua di Kesawan yang hidup lagi sebagai kopitiam, galeri sejarah, dan toko UMKM.', 'Usaha Seng Hap didirikan Tan Tang Ho pada 1881, dan gedungnya berdiri sekitar 1900. Setelah lama kosong, bangunan ini kini dipakai sebagai kopitiam dan ruang pamer sejarah Kesawan.', 'Kesawan', 'Jl. Jend. Ahmad Yani, Kesawan', 3.588480, 98.678510, false, 'Setiap hari, 07.00–24.00', '07:00:00', '23:59:00', true, false, 80, 45, NULL, NULL, 'aktif', 'https://rri.co.id/medan/iptek/2772486/seng-hap-1881-dari-bangunan-bersejarah-hingga-ruang-umkm-dan-kuliner', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(30, 4, 2, 'kuliner-jalan-semarang', 'Kawasan Kuliner Jl. Semarang', 'Pasar kuliner malam sepanjang sekitar 200 m, dari sate Padang sampai masakan Tionghoa.', 'Siang hari jalan ini berisi toko suku cadang, malamnya berubah jadi deretan kedai: sate Padang, lontong, sate kerang, fu yung hai, dan kwetiau. Harga sekitar Rp5.000–100.000. Sebagian menu tidak halal, jadi tanyakan ke penjual.', 'Pasar Baru', 'Jl. Semarang', 3.586050, 98.684860, false, 'Setiap hari, 17.00–01.00', '17:00:00', '23:59:00', true, false, 150, 60, NULL, NULL, 'aktif', 'https://gopay.co.id/blog/street-food-medan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(31, 4, 2, 'kuliner-selat-panjang', 'Kawasan Kuliner Jl. Selat Panjang', 'Deretan kedai pagi dan malam: bakmi, chasio, nasi ayam Hainan, dan jajanan.', 'Kedai di sini buka bergantian dari pagi sampai malam, misalnya Bakmi Khek SP5 (06.30–11.30 dan 17.00–23.00) dan Nasi Ayam Hainan SP10 yang halal. Harga dari Rp3.000 sampai sekitar Rp90.000.', 'Pasar Baru', 'Jl. Selat Panjang', 3.583180, 98.684770, false, 'Tiap kedai berbeda, antara 06.30 dan 23.00', '07:00:00', '23:00:00', true, false, 150, 45, NULL, NULL, 'aktif', 'https://www.indonesia.travel/id-id/travel-ideas/gastronomy/culinary-on-jalan-selat-panjang-medan/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(32, 4, 4, 'kuliner-pagaruyung', 'Kawasan Kuliner Jl. Pagaruyung', 'Kuliner malam di Kampung Madras: martabak, masakan India, dan jajanan lokal.', 'Jalan di Kampung Madras ini ramai pada malam hari dengan kedai martabak, roti canai, dan masakan India. Kecamatannya ditulis berbeda-beda oleh sumber, karena kawasannya berada di perbatasan Medan Petisah dan Medan Polonia.', 'Petisah Tengah', 'Jl. Pagaruyung, Kampung Madras', 3.584430, 98.670270, true, 'Malam, sekitar 18.00–02.00', '18:00:00', '23:59:00', true, false, 150, 45, NULL, NULL, 'aktif', 'https://www.inews.id/regional/sumut/6-wisata-kuliner-malam-di-medan-nomor-5-ucok-durian-buka-24-jam/all', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(33, 4, 1, 'kuliner-multatuli', 'Kawasan Kuliner Multatuli', 'Kumpulan kedai di Jl. Multatuli dan Komplek Multatuli Indah, dari mi Bangladesh 24 jam sampai nasi goreng.', 'Kawasan ini dikenal lewat warkop mi Bangladesh yang buka 24 jam, mulai sekitar Rp12.000, dan Nasi Goreng Wak Ribut di No. 83.', 'Hamdan', 'Jl. Multatuli dan Komplek Multatuli Indah', 3.578680, 98.678360, true, 'Tiap kedai berbeda, sebagian buka 24 jam', '00:00:00', '23:59:00', true, false, 150, 45, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/kuliner/d-8079041/rekomendasi-wisata-kuliner-malam-di-medan-buka-hingga-tengah-malam', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(34, 4, 4, 'bolu-meranti-kruing', 'Bolu Meranti Jl. Kruing', 'Toko bolu gulung Meranti, oleh-oleh khas Medan yang paling sering dibawa pulang.', 'Bolu gulung Meranti dijual sekitar Rp60.000–85.000 per kotak. Antrean sering panjang menjelang libur panjang.', 'Sekip', 'Jl. Kruing, simpang Jl. Razak', 3.594180, 98.666240, false, 'Setiap hari, 07.00–20.00', '07:00:00', '20:00:00', true, false, 80, 20, NULL, NULL, 'aktif', 'https://infopublik.id/kategori/ponxxi/870956/bolu-meranti-primadona-oleh-oleh-medan-di-pon-xxi-aceh-sumut', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(35, 4, 7, 'ucok-durian', 'Ucok Durian', 'Kedai durian paling terkenal di Medan, buka 24 jam.', 'Durian utuh dijual sekitar Rp50.000–200.000 per buah (2025), dan pembeli bisa langsung makan di tempat. Usahanya dimulai sejak 1980-an atau 1990-an, sumber berbeda.', 'Babura', 'Jl. K.H. Wahid Hasyim No. 30–32', 3.582760, 98.657500, false, 'Setiap hari, 24 jam', '00:00:00', '23:59:00', true, false, 80, 45, NULL, NULL, 'aktif', 'https://fahum.umsu.ac.id/info/ucok-durian-medan-surga-durian-legendaris-yang-wajib-dikunjungi/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(36, 4, 3, 'soto-sinar-pagi', 'Soto Sinar Pagi', 'Rumah makan soto Medan sejak 1962 di Jl. Sei Deli.', 'Soto berkuah santan dengan pilihan isi daging, ayam, udang, atau paru, sekitar Rp33.000 per porsi dengan nasi (2025). Punya cabang di Jl. Gatot Subroto dan Jl. Mangkubumi.', 'Silalas', 'Jl. Sei Deli No. 2D', 3.592220, 98.671070, false, 'Setiap hari, 07.00–15.30', '07:00:00', '15:00:00', true, false, 60, 40, NULL, NULL, 'aktif', 'https://www.jawapos.com/kuliner/2509260364/menjelajahi-3-kuliner-legendaris-di-medan-yang-tak-lekang-waktu', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(37, 4, 1, 'rumah-makan-tabona', 'Rumah Makan Tabona', 'Kari sapi dan kari ayam kampung legendaris, di Medan sejak 1982.', 'Tabona berdiri di Pematang Siantar pada 1972 lalu pindah ke Medan pada 1982. Kari dengan nasi atau bihun sekitar Rp60.000, porsi jumbo Rp90.000 (2024).', 'Aur', 'Jl. Mangkubumi No. 17', 3.581740, 98.681450, false, 'Setiap hari, 06.30–16.30', '07:00:00', '16:00:00', true, false, 60, 40, NULL, NULL, 'aktif', 'https://koran-jakarta.com/2024-09-23/tak-berdaya-di-rumah-makan-tabona/amp', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(38, 4, 9, 'sate-memeng', 'Sate Memeng', 'Kedai sate dan mie rebus Medan di Jl. Irian Barat, buka malam.', 'Menjual sate dan mie rebus Medan, sekitar Rp7.000–34.000 (2024). Di daftar tim tertulis "Mie Memeng".', 'Gang Buntu', 'Jl. Irian Barat No. 2', 3.589710, 98.681490, true, 'Setiap hari, 17.00–23.00', '17:00:00', '23:00:00', true, false, 100, 40, NULL, NULL, 'aktif', 'https://www.idntimes.com/food/dining-guide/rekomendasi-tempat-makan-halal-di-medan-c1c2-01-491ts-yd2zs6', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(39, 4, 3, 'kedai-kopi-apek', 'Kedai Kopi Apek', 'Kedai kopi tua di Kesawan, dinamai dari julukan pendirinya, Thia Tjo Lie.', 'Kedai ini sudah berjualan kopi sejak awal 1920-an. Minuman sekitar Rp22.000–30.000 (2023).', 'Kesawan', 'Jl. Hindu, Kesawan', 3.587580, 98.677330, false, 'Setiap hari, 06.00–14.00', '06:00:00', '14:00:00', true, false, 60, 30, NULL, NULL, 'aktif', 'https://www.goodnewsfromindonesia.id/2023/11/01/mencicipi-kenikmatan-kopi-apek-kedai-legendaris-dari-kota-medan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(40, 4, 3, 'tip-top-restaurant', 'Tip Top Restaurant', 'Restoran tertua di Medan: toko roti sejak 1929, di Kesawan sejak 1934.', 'Dimulai pada 1929 sebagai toko roti Jang Kie di Jl. Pandu, lalu pindah ke Kesawan pada 1934 dengan nama Tip Top. Es krim dan kue buatan sendiri masih jadi andalan. Menu Rp7.000–96.000 (2025).', 'Kesawan', 'Jl. Jend. Ahmad Yani No. 92 A-B', 3.586070, 98.679630, false, 'Setiap hari, 08.00–22.00', '08:00:00', '22:00:00', true, false, 60, 45, NULL, NULL, 'aktif', 'https://www.goodnewsfromindonesia.id/2025/12/05/tip-top-restaurant-tempat-kuliner-legendaris-di-medan-sejak-zaman-penjajahan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(41, 4, 10, 'rumah-makan-sipirok', 'Rumah Makan Sipirok', 'Rumah makan sop sumsum dan sop iga di Jl. Sunggal.', 'Menu andalannya sop sumsum dan sop iga, harga sekitar Rp7.000–93.000 (2024).', 'Sei Sikambing B', 'Jl. Sunggal No. 14', 3.584880, 98.641340, true, 'Setiap hari, 11.00–16.00', '11:00:00', '16:00:00', true, false, 120, 40, NULL, NULL, 'aktif', 'https://www.idntimes.com/food/dining-guide/rekomendasi-tempat-makan-halal-di-medan-c1c2-01-491ts-yd2zs6', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(42, 5, 11, 'taman-cadika', 'Taman Cadika Pramuka', 'Taman luas berdanau di Medan Johor, direvitalisasi dan dibuka lagi September 2024.', 'Masuk gratis. Ada danau, jalur lari, dan wahana berbayar seperti berkuda dan kayak. Revitalisasinya diresmikan pada 12 September 2024.', 'Pangkalan Masyhur', 'Jl. Karya Wisata', 3.530580, 98.659580, false, 'Terbuka setiap hari', '06:00:00', '22:00:00', true, false, 150, 60, NULL, NULL, 'aktif', 'https://sumut.antaranews.com/berita/588991/bobby-apresiasi-wajah-baru-taman-cadika-medan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(43, 5, 1, 'taman-ahmad-yani', 'Taman Ahmad Yani', 'Taman kota rindang di simpang Jl. Imam Bonjol dan Jl. Sudirman.', 'Taman gratis di tengah kawasan perkantoran yang sering dipakai Pemko Medan untuk acara warga.', 'Jati', 'Simpang Jl. Imam Bonjol – Jl. Sudirman', 3.576450, 98.677050, false, 'Terbuka 24 jam', '00:00:00', '23:59:00', true, false, 120, 20, NULL, NULL, 'aktif', 'https://sumut.idntimes.com/travel/destination/8-taman-kota-di-medan-tempat-terbaik-untuk-bersantai-dan-rekreasi-c1c2-01-2j5kq-dt7jcc', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(44, 5, 5, 'taman-beringin', 'Taman Beringin', 'Taman bermain di simpang Jl. Sudirman dan Jl. Teuku Cik Ditiro.', 'Taman gratis dengan area bermain anak. Pada April 2026 warga mengeluhkan sebagian permainan yang rusak.', 'Madras Hulu', 'Simpang Jl. Sudirman – Jl. Teuku Cik Ditiro', 3.576310, 98.669270, false, 'Terbuka setiap hari', '06:00:00', '22:00:00', true, false, 120, 20, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/berita/d-8455293/warga-berharap-fasilitas-di-taman-beringin-medan-lebih-terawat', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(45, 5, 2, 'taman-teladan', 'Taman Teladan', 'Taman di depan Stadion Teladan, ikut proyek revitalisasi stadion.', 'Per Januari 2026 revitalisasi Stadion Teladan dan tamannya diklaim sudah 97 persen. Cek kondisinya sebelum datang.', 'Teladan Barat', 'Jl. Stadion', 3.564960, 98.694210, false, 'Belum pasti, menunggu revitalisasi stadion selesai', NULL, NULL, true, false, 150, 20, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/berita/d-8309289/pemkot-klaim-progres-revitalisasi-stadion-teladan-capai-97-terkendala-apbn', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(46, 5, 7, 'taman-gajah-mada', 'Taman Gajah Mada', 'Ruang hijau favorit warga di Jl. Gajah Mada untuk olahraga dan bersantai.', 'Masuk gratis, parkir berbayar. Revitalisasinya direncanakan sejak Juli 2025. Jangan tertukar dengan Lapangan Gajah Mada di Jl. Krakatau.', NULL, 'Jl. Gajah Mada', 3.584160, 98.658860, false, 'Terbuka 24 jam', '00:00:00', '23:59:00', true, false, 120, 20, NULL, NULL, 'aktif', 'https://sumut.idntimes.com/travel/destination/5-fakta-unik-taman-gajah-mada-medan-ruang-hijau-favorit-warga-kota-c1c2-01-2j5kq-dst53d', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(47, 5, 9, 'taman-budaya-medan', 'Taman Budaya Medan', 'Kompleks kesenian di Jl. Perintis Kemerdekaan, tempat latihan dan pentas seniman Medan.', 'Gedung lama ini masih dipakai seniman untuk berlatih dan pentas. Kantor UPT Taman Budaya Sumatera Utara sendiri pindah ke kawasan PRSU pada 2020.', 'Gaharu', 'Jl. Perintis Kemerdekaan No. 33', 3.599510, 98.681400, true, 'Mengikuti jadwal acara', NULL, NULL, true, false, 120, 45, NULL, NULL, 'aktif', 'https://sumut.idntimes.com/news/sumatra-utara/5-fakta-unik-taman-budaya-kota-medan-01-2j5kq-7ktxm5', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(48, 5, 5, 'avros-park', 'Avros Park', 'Taman edukasi di tepi sungai dengan wahana outbound dan susur sungai.', 'Masuk gratis, wahana edukasi dan outbound mulai sekitar Rp20.000.', 'Kampung Baru', 'Jl. Avros No. 60B', 3.555840, 98.683970, false, 'Senin–Jumat 09.00–23.00, Sabtu–Minggu 08.00–23.00', '09:00:00', '23:00:00', true, false, 150, 90, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/wisata/d-7399272/avros-park-daya-tarik-lokasi-jam-buka-harga-dan-rute', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(49, 5, 4, 'lapangan-benteng', 'Lapangan Benteng', 'Ruang terbuka hijau untuk olahraga dan acara kota di Jl. Pengadilan.', 'Lapangan gratis untuk jogging dan olahraga, juga tempat acara kota sepanjang 2025–2026.', 'Petisah Tengah', 'Jl. Pengadilan', 3.588220, 98.673580, false, 'Terbuka 24 jam', '00:00:00', '23:59:00', true, false, 150, 20, NULL, NULL, 'aktif', 'https://kumparan.com/jendela-dunia/lapangan-benteng-medan-untuk-olahraga-fasilitas-alamat-jam-buka-dan-aksesnya-21iDvKcam1P', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(50, 6, 3, 'delipark-mall', 'DeliPark Mall', 'Mal di Podomoro City, dibuka akhir 2019 di bekas lahan Deli Plaza.', 'Mal di Podomoro City, dibuka akhir 2019 di bekas lahan Deli Plaza.', 'Kesawan', 'Jl. Putri Hijau No. 1', 3.594020, 98.674320, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.agungpodomoro.com/group/en/news/delipark-mall-the-new-icon-in-medan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07');
INSERT INTO public.tempat (id_tempat, id_kategori, id_kecamatan, slug, nama_tempat, ringkas, deskripsi, kelurahan, alamat, latitude, longitude, koordinat_perkiraan, keterangan_jam, jam_buka, jam_tutup, jam_contoh, harga_contoh, radius_checkin, durasi_singgah, image, wikidata, status, source_url, created_at, updated_at) VALUES
	(51, 6, 5, 'sun-plaza', 'Sun Plaza', 'Mal besar di Jl. Zainul Arifin, dibuka 2004.', 'Mal besar di Jl. Zainul Arifin, dibuka 2004.', 'Madras Hulu', 'Jl. K.H. Zainul Arifin No. 7', 3.582260, 98.671910, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.jawapos.com/travelling/2605120204/liburan-tidak-lengkap-tanpa-mampir-ke-12-mall-terbaik-di-medan-ini-surga-belanja-sampai-tempat-hangout-viral-yang-wajib-anda-kunjungi', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(52, 6, 9, 'centre-point-mall', 'Centre Point Mall', 'Mal di Jl. Jawa, dekat Stasiun Medan.', 'Mal di Jl. Jawa, dekat Stasiun Medan.', 'Gang Buntu', 'Jl. Jawa No. 8', 3.591720, 98.680990, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.jawapos.com/travelling/2605120204/liburan-tidak-lengkap-tanpa-mampir-ke-12-mall-terbaik-di-medan-ini-surga-belanja-sampai-tempat-hangout-viral-yang-wajib-anda-kunjungi', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(53, 6, 4, 'plaza-medan-fair', 'Plaza Medan Fair', 'Mal di Jl. Gatot Subroto, dibuka 2004.', 'Mal di Jl. Gatot Subroto, dibuka 2004.', 'Sekip', 'Jl. Gatot Subroto No. 30', 3.592870, 98.663740, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.jawapos.com/travelling/2605120204/liburan-tidak-lengkap-tanpa-mampir-ke-12-mall-terbaik-di-medan-ini-surga-belanja-sampai-tempat-hangout-viral-yang-wajib-anda-kunjungi', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(54, 6, 10, 'manhattan-times-square', 'Manhattan Times Square', 'Mal di Jl. Gatot Subroto bagian barat, dibuka 2017.', 'Mal di Jl. Gatot Subroto bagian barat, dibuka 2017.', 'Sei Sikambing B', 'Jl. Gatot Subroto No. 217', 3.590830, 98.626570, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/berita/d-6526632/deretan-mal-mewah-di-kota-medan-salah-satunya-terbaik-se-indonesia', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(55, 6, 10, 'ringroad-city-walks', 'Ringroad City Walks', 'Mal di Jl. Ring Road, dibuka 2015.', 'Mal di Jl. Ring Road, dibuka 2015.', 'Sunggal', 'Jl. Ring Road / Jl. Gagak Hitam', 3.575750, 98.625550, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/berita/d-6526632/deretan-mal-mewah-di-kota-medan-salah-satunya-terbaik-se-indonesia', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(56, 6, 4, 'cambridge-city-square', 'Cambridge City Square', 'Mal dan apartemen di Jl. S. Parman.', 'Mal dan apartemen di Jl. S. Parman.', 'Petisah Tengah', 'Jl. S. Parman', 3.584950, 98.667350, false, 'Sekitar 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://economy.okezone.com/read/2023/04/27/470/2804438/9-mall-paling-mewah-dan-terkenal-di-kota-medan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(57, 6, 12, 'focal-point', 'Focal Point', 'Mal di Jl. Ring Road, dibuka 2014.', 'Mal di Jl. Ring Road, dibuka 2014.', 'Asam Kumbang', 'Jl. Arteri Ring Road / Jl. Gagak Hitam', 3.567030, 98.625800, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.rumah123.com/explore/kota-medan/focal-point-medan/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(58, 6, 13, 'plaza-millenium', 'Plaza Millenium', 'Pusat gawai dan elektronik di Jl. Kapten Muslim.', 'Pusat gawai dan elektronik di Jl. Kapten Muslim.', 'Dwi Kora', 'Jl. Kapten Muslim', 3.599350, 98.644320, false, 'Jam buka belum terverifikasi', NULL, NULL, true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.openstreetmap.org/way/894120597', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(59, 6, 2, 'medan-mall', 'Medan Mall', 'Pusat belanja grosir legendaris di dekat Pusat Pasar.', 'Pusat belanja grosir legendaris di dekat Pusat Pasar.', 'Pusat Pasar', 'Jl. Letjen M.T. Haryono', 3.588550, 98.686460, false, 'Jam buka belum terverifikasi', NULL, NULL, true, false, 100, 45, NULL, NULL, 'aktif', 'https://economy.okezone.com/read/2023/04/27/470/2804438/9-mall-paling-mewah-dan-terkenal-di-kota-medan', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(60, 6, 14, 'thamrin-plaza', 'Thamrin Plaza', 'Plaza lama di Jl. Thamrin. Sebagian tutup: Matahari berhenti beroperasi sejak 1 April 2025.', 'Plaza lama di Jl. Thamrin. Sebagian tutup: Matahari berhenti beroperasi sejak 1 April 2025.', 'Sei Rengas II', 'Jl. M.H. Thamrin No. 75R', 3.586830, 98.692910, false, 'Jam buka belum terverifikasi', NULL, NULL, true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/berita/d-7853535/matahari-thamrin-plaza-tutup-warga-medan-kenang-momen-belanja-semasa-kecil', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(61, 6, 5, 'lippo-plaza-medan', 'Lippo Plaza Medan', 'Gedung 15 lantai berisi mal, kantor, rumah sakit, dan kampus.', 'Gedung 15 lantai berisi mal, kantor, rumah sakit, dan kampus.', 'Suka Damai', 'Jl. Imam Bonjol No. 6', 3.586440, 98.673350, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.jawapos.com/travelling/2605120204/liburan-tidak-lengkap-tanpa-mampir-ke-12-mall-terbaik-di-medan-ini-surga-belanja-sampai-tempat-hangout-viral-yang-wajib-anda-kunjungi', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(62, 6, 8, 'suzuya-marelan', 'Suzuya Mall Marelan', 'Mal di Medan Marelan, wilayah utara kota.', 'Mal di Medan Marelan, wilayah utara kota.', 'Tanah Enam Ratus', 'Medan Marelan', 3.678470, 98.657070, false, 'Setiap hari, 10.00–22.00', '10:00:00', '22:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.openstreetmap.org/way/645564631', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(63, 6, 3, 'irian-supermarket', 'Irian Supermarket Jl. Karya', 'Jaringan swalayan lokal Medan, cabang Jl. Karya.', 'Jaringan swalayan lokal Medan, cabang Jl. Karya.', 'Sei Agul', 'Jl. Karya No. 116', 3.609030, 98.665580, false, 'Jam buka belum terverifikasi', NULL, NULL, true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.bca.co.id/id/promo-bca/2025/03/12/02/45/Irian-Supermarket', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(64, 6, 14, 'pasar-ramai', 'Pasar Ramai', 'Pasar grosir dan tekstil di samping Thamrin Plaza.', 'Pasar grosir dan tekstil di samping Thamrin Plaza.', 'Sei Rengas II', 'Jl. M.H. Thamrin', 3.586500, 98.692500, true, 'Pagi sampai sore', '08:00:00', '17:00:00', true, false, 100, 45, NULL, NULL, 'aktif', 'https://www.holidify.com/pages/shopping-in-medan-2705.html', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(65, 7, 2, 'kolam-renang-paradiso', 'Kolam Renang Paradiso', 'Kolam renang tertua di Medan, sejak 1924.', 'Dibangun pada 1924 dan dulu hanya untuk orang Eropa. Sampai April 2026 kolam ini masih buka untuk umum.', 'Mesjid', 'Jl. Sisingamangaraja No. 6', 3.579940, 98.684950, false, 'Sekitar 07.00–18.00', '07:00:00', '18:00:00', true, true, 100, 45, NULL, NULL, 'aktif', 'https://www.detik.com/sumut/budaya/d-8445606/paradiso-kolam-renang-tertua-di-medan-sejak-1924', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(66, 7, 9, 'kolam-renang-deli', 'Kolam Renang Deli', 'Kolam renang lama di Jl. Sutomo Ujung. Sebagian kolam tidak berfungsi (Agustus 2025).', 'Saat dikunjungi wali kota pada Agustus 2025, satu dari lima kolamnya tidak berfungsi. Cek kondisinya sebelum datang.', 'Gaharu', 'Jl. Sutomo Ujung', 3.602660, 98.680760, false, 'Sekitar 07.00–18.00', '07:00:00', '18:00:00', true, true, 100, 45, NULL, NULL, 'aktif', 'https://www.sumutinsider.com/sumatera-utara/80015706882/miris-ikon-kolam-renang-deli-medan-terbengkalai', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(67, 7, 13, 'kolam-renang-tirta-kartika', 'Kolam Renang Tirta Kartika', 'Kolam renang di Medan Helvetia yang juga dipakai latihan renang prajurit.', 'Kolam renang di Medan Helvetia yang juga dipakai latihan renang prajurit.', 'Helvetia Tengah', 'Jl. H.A. Manaf Lubis Blok G No. 20A', 3.605330, 98.639850, true, 'Sekitar 07.00–19.00', '07:00:00', '19:00:00', true, true, 100, 45, NULL, NULL, 'aktif', 'https://renangloka.com/pool/kolam-renang-tirta-kartika/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(68, 7, 13, 'bumi-asri-waterpark', 'Bumi Asri Waterpark', 'Kolam renang dan waterpark di Komplek Bumi Asri.', 'Kolam renang dan waterpark di Komplek Bumi Asri.', 'Cinta Damai', 'Jl. Blok G, Komplek Bumi Asri', 3.600290, 98.623790, false, 'Sekitar 08.00–17.00', '08:00:00', '17:00:00', true, true, 100, 45, NULL, NULL, 'aktif', 'https://www.rumah123.com/explore/kota-medan/kolam-renang-medan/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07'),
	(69, 7, 12, 'kolam-renang-primbana', 'Kolam Renang Primbana', 'Kolam renang di Sempakata, Medan Selayang. Senin tutup.', 'Kolam renang di Sempakata, Medan Selayang. Senin tutup.', 'Sempakata', 'Jl. Melati Raya No. 55', 3.539610, 98.628830, false, 'Selasa–Minggu, 08.00–17.00', '08:00:00', '17:00:00', true, true, 100, 45, NULL, NULL, 'aktif', 'https://renangloka.com/pool/kolam-renang-primbana/', '2026-11-06 14:43:38+07', '2026-11-06 14:43:38+07');


--
-- Data for Name: tiket; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.tiket (id_tiket, id_tempat, jenis_pengunjung, harga) VALUES
	(1, 1, 'Dewasa', 10000),
	(2, 1, 'Anak', 5000),
	(3, 3, 'Umum, termasuk pemandu', 35000),
	(4, 8, 'Umum', 3000),
	(5, 23, 'Domestik', 75000),
	(6, 23, 'Pelajar atau rombongan', 25000),
	(7, 23, 'Wisatawan mancanegara', 150000),
	(8, 24, 'Umum', 12000),
	(9, 65, 'Umum', 18000),
	(10, 66, 'Hari kerja', 12000),
	(11, 66, 'Akhir pekan', 15000),
	(12, 67, 'Umum', 20000),
	(13, 68, 'Hari kerja', 21000),
	(14, 68, 'Akhir pekan', 26000),
	(15, 69, 'Reguler', 15000),
	(16, 69, 'VIP', 20000);


--
-- Name: cara_menuju_id_cara_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cara_menuju_id_cara_seq', 18, true);


--
-- Name: entitas_id_entitas_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.entitas_id_entitas_seq', 18, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: fasilitas_tempat_id_fasilitas_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.fasilitas_tempat_id_fasilitas_seq', 19, true);


--
-- Name: jalur_id_jalur_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jalur_id_jalur_seq', 3, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: kategori_id_kategori_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.kategori_id_kategori_seq', 7, true);


--
-- Name: kecamatan_id_kecamatan_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.kecamatan_id_kecamatan_seq', 14, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 16, true);


--
-- Name: relasi_id_relasi_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.relasi_id_relasi_seq', 106, true);


--
-- Name: tempat_id_tempat_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tempat_id_tempat_seq', 69, true);


--
-- Name: tiket_id_tiket_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tiket_id_tiket_seq', 16, true);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: cara_menuju cara_menuju_id_tempat_urutan_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cara_menuju
    ADD CONSTRAINT cara_menuju_id_tempat_urutan_unique UNIQUE (id_tempat, urutan);


--
-- Name: cara_menuju cara_menuju_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cara_menuju
    ADD CONSTRAINT cara_menuju_pkey PRIMARY KEY (id_cara);


--
-- Name: entitas entitas_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entitas
    ADD CONSTRAINT entitas_pkey PRIMARY KEY (id_entitas);


--
-- Name: entitas entitas_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entitas
    ADD CONSTRAINT entitas_slug_unique UNIQUE (slug);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: fasilitas_tempat fasilitas_tempat_id_tempat_urutan_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fasilitas_tempat
    ADD CONSTRAINT fasilitas_tempat_id_tempat_urutan_unique UNIQUE (id_tempat, urutan);


--
-- Name: fasilitas_tempat fasilitas_tempat_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fasilitas_tempat
    ADD CONSTRAINT fasilitas_tempat_pkey PRIMARY KEY (id_fasilitas);


--
-- Name: jalur jalur_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jalur
    ADD CONSTRAINT jalur_pkey PRIMARY KEY (id_jalur);


--
-- Name: jalur_singgah jalur_singgah_id_jalur_id_tempat_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jalur_singgah
    ADD CONSTRAINT jalur_singgah_id_jalur_id_tempat_unique UNIQUE (id_jalur, id_tempat);


--
-- Name: jalur_singgah jalur_singgah_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jalur_singgah
    ADD CONSTRAINT jalur_singgah_pkey PRIMARY KEY (id_jalur, urutan);


--
-- Name: jalur jalur_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jalur
    ADD CONSTRAINT jalur_slug_unique UNIQUE (slug);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: kategori kategori_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kategori
    ADD CONSTRAINT kategori_kode_unique UNIQUE (kode);


--
-- Name: kategori kategori_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kategori
    ADD CONSTRAINT kategori_pkey PRIMARY KEY (id_kategori);


--
-- Name: kecamatan kecamatan_nama_kecamatan_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kecamatan
    ADD CONSTRAINT kecamatan_nama_kecamatan_unique UNIQUE (nama_kecamatan);


--
-- Name: kecamatan kecamatan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kecamatan
    ADD CONSTRAINT kecamatan_pkey PRIMARY KEY (id_kecamatan);


--
-- Name: kecamatan kecamatan_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.kecamatan
    ADD CONSTRAINT kecamatan_slug_unique UNIQUE (slug);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: predikat predikat_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.predikat
    ADD CONSTRAINT predikat_pkey PRIMARY KEY (kode);


--
-- Name: relasi relasi_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.relasi
    ADD CONSTRAINT relasi_pkey PRIMARY KEY (id_relasi);


--
-- Name: relasi relasi_subjek_predikat_objek_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.relasi
    ADD CONSTRAINT relasi_subjek_predikat_objek_unique UNIQUE (subjek, predikat, objek);


--
-- Name: tempat tempat_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tempat
    ADD CONSTRAINT tempat_pkey PRIMARY KEY (id_tempat);


--
-- Name: tempat tempat_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tempat
    ADD CONSTRAINT tempat_slug_unique UNIQUE (slug);


--
-- Name: tiket tiket_id_tempat_jenis_pengunjung_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tiket
    ADD CONSTRAINT tiket_id_tempat_jenis_pengunjung_unique UNIQUE (id_tempat, jenis_pengunjung);


--
-- Name: tiket tiket_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tiket
    ADD CONSTRAINT tiket_pkey PRIMARY KEY (id_tiket);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: ix_entitas_jenis; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_entitas_jenis ON public.entitas USING btree (jenis);


--
-- Name: ix_relasi_objek; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_relasi_objek ON public.relasi USING btree (objek);


--
-- Name: ix_relasi_subjek; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_relasi_subjek ON public.relasi USING btree (subjek);


--
-- Name: ix_tempat_kategori; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_tempat_kategori ON public.tempat USING btree (id_kategori);


--
-- Name: ix_tempat_kecamatan; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_tempat_kecamatan ON public.tempat USING btree (id_kecamatan);


--
-- Name: ix_tempat_koordinat; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_tempat_koordinat ON public.tempat USING btree (latitude, longitude);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: entitas entitas_ikut_ganti; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER entitas_ikut_ganti AFTER UPDATE OF slug ON public.entitas FOR EACH ROW EXECUTE FUNCTION public.trg_simpul_jaga_relasi();


--
-- Name: entitas entitas_jaga_hapus; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER entitas_jaga_hapus BEFORE DELETE ON public.entitas FOR EACH ROW EXECUTE FUNCTION public.trg_simpul_jaga_relasi();


--
-- Name: kecamatan kecamatan_ikut_ganti; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER kecamatan_ikut_ganti AFTER UPDATE OF slug ON public.kecamatan FOR EACH ROW EXECUTE FUNCTION public.trg_simpul_jaga_relasi();


--
-- Name: kecamatan kecamatan_jaga_hapus; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER kecamatan_jaga_hapus BEFORE DELETE ON public.kecamatan FOR EACH ROW EXECUTE FUNCTION public.trg_simpul_jaga_relasi();


--
-- Name: relasi relasi_cek_simpul; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER relasi_cek_simpul BEFORE INSERT OR UPDATE ON public.relasi FOR EACH ROW EXECUTE FUNCTION public.trg_relasi_cek_simpul();


--
-- Name: tempat tempat_ikut_ganti; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER tempat_ikut_ganti AFTER UPDATE OF slug ON public.tempat FOR EACH ROW EXECUTE FUNCTION public.trg_simpul_jaga_relasi();


--
-- Name: tempat tempat_jaga_hapus; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER tempat_jaga_hapus BEFORE DELETE ON public.tempat FOR EACH ROW EXECUTE FUNCTION public.trg_simpul_jaga_relasi();


--
-- Name: tempat tempat_updated_at; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER tempat_updated_at BEFORE UPDATE ON public.tempat FOR EACH ROW EXECUTE FUNCTION public.trg_set_updated_at();


--
-- Name: cara_menuju cara_menuju_id_tempat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cara_menuju
    ADD CONSTRAINT cara_menuju_id_tempat_foreign FOREIGN KEY (id_tempat) REFERENCES public.tempat(id_tempat) ON DELETE CASCADE;


--
-- Name: fasilitas_tempat fasilitas_tempat_id_tempat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fasilitas_tempat
    ADD CONSTRAINT fasilitas_tempat_id_tempat_foreign FOREIGN KEY (id_tempat) REFERENCES public.tempat(id_tempat) ON DELETE CASCADE;


--
-- Name: jalur_singgah jalur_singgah_id_jalur_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jalur_singgah
    ADD CONSTRAINT jalur_singgah_id_jalur_foreign FOREIGN KEY (id_jalur) REFERENCES public.jalur(id_jalur) ON DELETE CASCADE;


--
-- Name: jalur_singgah jalur_singgah_id_tempat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jalur_singgah
    ADD CONSTRAINT jalur_singgah_id_tempat_foreign FOREIGN KEY (id_tempat) REFERENCES public.tempat(id_tempat) ON DELETE RESTRICT;


--
-- Name: relasi relasi_predikat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.relasi
    ADD CONSTRAINT relasi_predikat_foreign FOREIGN KEY (predikat) REFERENCES public.predikat(kode) ON DELETE RESTRICT;


--
-- Name: tempat tempat_id_kategori_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tempat
    ADD CONSTRAINT tempat_id_kategori_foreign FOREIGN KEY (id_kategori) REFERENCES public.kategori(id_kategori) ON DELETE RESTRICT;


--
-- Name: tempat tempat_id_kecamatan_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tempat
    ADD CONSTRAINT tempat_id_kecamatan_foreign FOREIGN KEY (id_kecamatan) REFERENCES public.kecamatan(id_kecamatan) ON DELETE RESTRICT;


--
-- Name: tiket tiket_id_tempat_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tiket
    ADD CONSTRAINT tiket_id_tempat_foreign FOREIGN KEY (id_tempat) REFERENCES public.tempat(id_tempat) ON DELETE CASCADE;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: -
--

GRANT USAGE ON SCHEMA public TO medanvisit_baca;
GRANT USAGE ON SCHEMA public TO medanvisit_kurator;


--
-- Name: FUNCTION fn_buka_pada(p_id_tempat integer, p_jam time without time zone); Type: ACL; Schema: public; Owner: -
--

GRANT ALL ON FUNCTION public.fn_buka_pada(p_id_tempat integer, p_jam time without time zone) TO medanvisit_baca;
GRANT ALL ON FUNCTION public.fn_buka_pada(p_id_tempat integer, p_jam time without time zone) TO medanvisit_kurator;


--
-- Name: FUNCTION fn_jarak_meter(lat1 double precision, lng1 double precision, lat2 double precision, lng2 double precision); Type: ACL; Schema: public; Owner: -
--

GRANT ALL ON FUNCTION public.fn_jarak_meter(lat1 double precision, lng1 double precision, lat2 double precision, lng2 double precision) TO medanvisit_baca;
GRANT ALL ON FUNCTION public.fn_jarak_meter(lat1 double precision, lng1 double precision, lat2 double precision, lng2 double precision) TO medanvisit_kurator;


--
-- Name: FUNCTION fn_tempat_terdekat(p_lat double precision, p_lng double precision, p_batas_m integer); Type: ACL; Schema: public; Owner: -
--

GRANT ALL ON FUNCTION public.fn_tempat_terdekat(p_lat double precision, p_lng double precision, p_batas_m integer) TO medanvisit_baca;
GRANT ALL ON FUNCTION public.fn_tempat_terdekat(p_lat double precision, p_lng double precision, p_batas_m integer) TO medanvisit_kurator;


--
-- Name: PROCEDURE sp_hitung_relasi_dekat(IN p_batas_m integer); Type: ACL; Schema: public; Owner: -
--

GRANT ALL ON PROCEDURE public.sp_hitung_relasi_dekat(IN p_batas_m integer) TO medanvisit_kurator;


--
-- Name: TABLE cache; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.cache TO medanvisit_baca;
GRANT SELECT ON TABLE public.cache TO medanvisit_kurator;


--
-- Name: TABLE cache_locks; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.cache_locks TO medanvisit_baca;
GRANT SELECT ON TABLE public.cache_locks TO medanvisit_kurator;


--
-- Name: TABLE cara_menuju; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.cara_menuju TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.cara_menuju TO medanvisit_kurator;


--
-- Name: SEQUENCE cara_menuju_id_cara_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.cara_menuju_id_cara_seq TO medanvisit_kurator;


--
-- Name: TABLE entitas; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.entitas TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.entitas TO medanvisit_kurator;


--
-- Name: SEQUENCE entitas_id_entitas_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.entitas_id_entitas_seq TO medanvisit_kurator;


--
-- Name: TABLE failed_jobs; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.failed_jobs TO medanvisit_baca;
GRANT SELECT ON TABLE public.failed_jobs TO medanvisit_kurator;


--
-- Name: SEQUENCE failed_jobs_id_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.failed_jobs_id_seq TO medanvisit_kurator;


--
-- Name: TABLE fasilitas_tempat; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.fasilitas_tempat TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.fasilitas_tempat TO medanvisit_kurator;


--
-- Name: SEQUENCE fasilitas_tempat_id_fasilitas_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.fasilitas_tempat_id_fasilitas_seq TO medanvisit_kurator;


--
-- Name: TABLE jalur; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.jalur TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.jalur TO medanvisit_kurator;


--
-- Name: SEQUENCE jalur_id_jalur_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.jalur_id_jalur_seq TO medanvisit_kurator;


--
-- Name: TABLE jalur_singgah; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.jalur_singgah TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.jalur_singgah TO medanvisit_kurator;


--
-- Name: TABLE job_batches; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.job_batches TO medanvisit_baca;
GRANT SELECT ON TABLE public.job_batches TO medanvisit_kurator;


--
-- Name: TABLE jobs; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.jobs TO medanvisit_baca;
GRANT SELECT ON TABLE public.jobs TO medanvisit_kurator;


--
-- Name: SEQUENCE jobs_id_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.jobs_id_seq TO medanvisit_kurator;


--
-- Name: TABLE kategori; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.kategori TO medanvisit_baca;
GRANT SELECT ON TABLE public.kategori TO medanvisit_kurator;


--
-- Name: SEQUENCE kategori_id_kategori_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.kategori_id_kategori_seq TO medanvisit_kurator;


--
-- Name: TABLE kecamatan; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.kecamatan TO medanvisit_baca;
GRANT SELECT ON TABLE public.kecamatan TO medanvisit_kurator;


--
-- Name: SEQUENCE kecamatan_id_kecamatan_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.kecamatan_id_kecamatan_seq TO medanvisit_kurator;


--
-- Name: TABLE migrations; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.migrations TO medanvisit_baca;
GRANT SELECT ON TABLE public.migrations TO medanvisit_kurator;


--
-- Name: SEQUENCE migrations_id_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.migrations_id_seq TO medanvisit_kurator;


--
-- Name: TABLE predikat; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.predikat TO medanvisit_baca;
GRANT SELECT ON TABLE public.predikat TO medanvisit_kurator;


--
-- Name: TABLE relasi; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.relasi TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.relasi TO medanvisit_kurator;


--
-- Name: SEQUENCE relasi_id_relasi_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.relasi_id_relasi_seq TO medanvisit_kurator;


--
-- Name: TABLE tempat; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.tempat TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.tempat TO medanvisit_kurator;


--
-- Name: SEQUENCE tempat_id_tempat_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.tempat_id_tempat_seq TO medanvisit_kurator;


--
-- Name: TABLE tiket; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.tiket TO medanvisit_baca;
GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.tiket TO medanvisit_kurator;


--
-- Name: SEQUENCE tiket_id_tiket_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.tiket_id_tiket_seq TO medanvisit_kurator;


--
-- Name: TABLE v_simpul; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.v_simpul TO medanvisit_baca;
GRANT SELECT ON TABLE public.v_simpul TO medanvisit_kurator;


--
-- Name: TABLE v_relasi_kalimat; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.v_relasi_kalimat TO medanvisit_baca;
GRANT SELECT ON TABLE public.v_relasi_kalimat TO medanvisit_kurator;


--
-- Name: TABLE v_statistik_kategori; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.v_statistik_kategori TO medanvisit_baca;
GRANT SELECT ON TABLE public.v_statistik_kategori TO medanvisit_kurator;


--
-- Name: TABLE v_tempat_lengkap; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT ON TABLE public.v_tempat_lengkap TO medanvisit_baca;
GRANT SELECT ON TABLE public.v_tempat_lengkap TO medanvisit_kurator;


--
-- PostgreSQL database dump complete
--

