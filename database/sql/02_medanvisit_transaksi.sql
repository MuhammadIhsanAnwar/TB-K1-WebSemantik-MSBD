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
-- Name: fn_hitung_ringkasan_ulasan(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_hitung_ringkasan_ulasan(p_id_tempat integer) RETURNS void
    LANGUAGE sql
    AS $$
    INSERT INTO ringkasan_ulasan (id_tempat, jumlah_ulasan, rata_rata_bintang, diperbarui_pada)
    SELECT p_id_tempat, count(*), round(avg(bintang), 2), now()
    FROM ulasan WHERE id_tempat = p_id_tempat AND status = 'tayang'
    ON CONFLICT (id_tempat) DO UPDATE
        SET jumlah_ulasan = EXCLUDED.jumlah_ulasan,
            rata_rata_bintang = EXCLUDED.rata_rata_bintang,
            diperbarui_pada = EXCLUDED.diperbarui_pada;
$$;


--
-- Name: fn_kode_acak(text, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_kode_acak(p_awalan text, p_panjang integer) RETURNS text
    LANGUAGE sql
    AS $$
    SELECT p_awalan || upper(substr(md5(random()::text || clock_timestamp()::text), 1, p_panjang))
$$;


--
-- Name: fn_saldo_poin(bigint); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_saldo_poin(p_id_pengguna bigint) RETURNS integer
    LANGUAGE sql STABLE
    AS $$
    SELECT COALESCE(sum(poin), 0)::integer FROM riwayat_poin WHERE id_pengguna = p_id_pengguna
$$;


--
-- Name: sp_bayar_pesanan(character varying, character varying, character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.sp_bayar_pesanan(IN p_kode character varying, IN p_referensi character varying DEFAULT NULL::character varying, INOUT p_kode_qr character varying DEFAULT NULL::character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    p pesanan%ROWTYPE;
BEGIN
    SELECT * INTO p FROM pesanan WHERE kode = p_kode FOR UPDATE;
    IF p.id_pesanan IS NULL THEN
        RAISE EXCEPTION 'Pesanan % tidak ditemukan', p_kode;
    END IF;
    IF p.status <> 'menunggu' THEN
        RAISE EXCEPTION 'Pesanan % berstatus %, tidak bisa dibayar', p_kode, p.status;
    END IF;
    IF p.batas_bayar < now() THEN
        UPDATE pesanan SET status = 'kedaluwarsa', updated_at = now() WHERE id_pesanan = p.id_pesanan;
        RAISE NOTICE 'Pesanan % sudah lewat batas bayar dan ditandai kedaluwarsa', p_kode;
        RETURN;
    END IF;

    INSERT INTO pembayaran (id_pesanan, metode, jumlah, referensi)
    VALUES (p.id_pesanan, p.metode, p.total, p_referensi);
    UPDATE pesanan SET status = 'lunas', updated_at = now() WHERE id_pesanan = p.id_pesanan;
    p_kode_qr := fn_kode_acak('QR-', 12);
    INSERT INTO e_tiket (id_pesanan, kode_qr) VALUES (p.id_pesanan, p_kode_qr);
END $$;


--
-- Name: sp_buat_pesanan(bigint, character varying, character varying, character varying, integer, date, character varying, jsonb, character varying); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.sp_buat_pesanan(IN p_id_pengguna bigint, IN p_nama character varying, IN p_email character varying, IN p_hp character varying, IN p_id_tempat integer, IN p_tanggal date, IN p_metode character varying, IN p_item jsonb, INOUT p_kode character varying DEFAULT NULL::character varying)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id bigint;
BEGIN
    IF jsonb_array_length(p_item) = 0 THEN
        RAISE EXCEPTION 'Pesanan harus berisi minimal satu tiket';
    END IF;
    IF p_tanggal < (now() AT TIME ZONE 'Asia/Jakarta')::date THEN
        RAISE EXCEPTION 'Tanggal kunjungan tidak boleh di masa lalu';
    END IF;

    p_kode := fn_kode_acak('MV-', 8);
    INSERT INTO pesanan (kode, id_pengguna, nama_pemesan, email_pemesan, hp_pemesan,
                         id_tempat, tanggal_kunjungan, metode, batas_bayar, created_at, updated_at)
    VALUES (p_kode, p_id_pengguna, p_nama, p_email, p_hp,
            p_id_tempat, p_tanggal, p_metode, now() + interval '15 minutes', now(), now())
    RETURNING id_pesanan INTO v_id;

    INSERT INTO pesanan_item (id_pesanan, jenis_pengunjung, harga, jumlah)
    SELECT v_id, x->>'jenis', (x->>'harga')::integer, (x->>'jumlah')::smallint
    FROM jsonb_array_elements(p_item) AS x
    WHERE (x->>'jumlah')::integer > 0;
END $$;


--
-- Name: sp_kedaluwarsakan_pesanan(); Type: PROCEDURE; Schema: public; Owner: -
--

CREATE PROCEDURE public.sp_kedaluwarsakan_pesanan()
    LANGUAGE sql
    AS $$
    UPDATE pesanan SET status = 'kedaluwarsa', updated_at = now()
    WHERE status = 'menunggu' AND batas_bayar < now();
$$;


--
-- Name: trg_checkin_poin(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_checkin_poin() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO riwayat_poin (id_pengguna, poin, sumber, id_sumber, keterangan)
    VALUES (NEW.id_pengguna, 10, 'checkin', NEW.id_checkin, 'Check-in di tempat #' || NEW.id_tempat);
    RETURN NULL;
END $$;


--
-- Name: trg_penukaran_cek(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_penukaran_cek() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v voucher%ROWTYPE;
BEGIN
    SELECT * INTO v FROM voucher WHERE id_voucher = NEW.id_voucher FOR UPDATE;
    IF v.id_voucher IS NULL THEN
        RAISE EXCEPTION 'Voucher #% tidak ditemukan', NEW.id_voucher;
    END IF;
    IF v.berlaku_sampai < current_date THEN
        RAISE EXCEPTION 'Voucher "%" sudah tidak berlaku', v.nama;
    END IF;
    IF v.stok <= 0 THEN
        RAISE EXCEPTION 'Stok voucher "%" habis', v.nama;
    END IF;
    IF fn_saldo_poin(NEW.id_pengguna) < v.poin THEN
        RAISE EXCEPTION 'Poin tidak cukup: perlu %, saldo %', v.poin, fn_saldo_poin(NEW.id_pengguna);
    END IF;
    UPDATE voucher SET stok = stok - 1, updated_at = now() WHERE id_voucher = v.id_voucher;
    NEW.poin := v.poin;
    NEW.kode := COALESCE(NEW.kode, fn_kode_acak('V-', 6));
    RETURN NEW;
END $$;


--
-- Name: trg_penukaran_poin(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_penukaran_poin() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO riwayat_poin (id_pengguna, poin, sumber, id_sumber, keterangan)
    VALUES (NEW.id_pengguna, -NEW.poin, 'penukaran', NEW.id_penukaran, 'Tukar voucher ' || NEW.kode);
    RETURN NULL;
END $$;


--
-- Name: trg_pesanan_hitung_total(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_pesanan_hitung_total() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id bigint := COALESCE(NEW.id_pesanan, OLD.id_pesanan);
BEGIN
    UPDATE pesanan
    SET total = (SELECT COALESCE(sum(subtotal), 0) FROM pesanan_item WHERE id_pesanan = v_id),
        updated_at = now()
    WHERE id_pesanan = v_id;
    RETURN NULL;
END $$;


--
-- Name: trg_ulasan_ringkasan(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.trg_ulasan_ringkasan() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF TG_OP IN ('UPDATE', 'DELETE') THEN
        PERFORM fn_hitung_ringkasan_ulasan(OLD.id_tempat);
    END IF;
    IF TG_OP IN ('INSERT', 'UPDATE') THEN
        PERFORM fn_hitung_ringkasan_ulasan(NEW.id_tempat);
    END IF;
    RETURN NULL;
END $$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: checkin; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.checkin (
    id_checkin bigint NOT NULL,
    id_pengguna bigint NOT NULL,
    id_tempat integer NOT NULL,
    jarak_m smallint NOT NULL,
    tanggal date DEFAULT ((now() AT TIME ZONE 'Asia/Jakarta'::text))::date NOT NULL,
    waktu timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT ck_checkin_jarak CHECK ((jarak_m >= 0))
);


--
-- Name: checkin_id_checkin_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.checkin_id_checkin_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: checkin_id_checkin_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.checkin_id_checkin_seq OWNED BY public.checkin.id_checkin;


--
-- Name: e_tiket; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.e_tiket (
    id_etiket bigint NOT NULL,
    id_pesanan bigint NOT NULL,
    kode_qr character varying(40) NOT NULL,
    diterbitkan_pada timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    dipakai_pada timestamp(0) with time zone
);


--
-- Name: e_tiket_id_etiket_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.e_tiket_id_etiket_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: e_tiket_id_etiket_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.e_tiket_id_etiket_seq OWNED BY public.e_tiket.id_etiket;


--
-- Name: log_pencarian; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.log_pencarian (
    id_log bigint NOT NULL,
    kata_kunci character varying(120) NOT NULL,
    jumlah_hasil smallint NOT NULL,
    waktu timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: log_pencarian_id_log_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.log_pencarian_id_log_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: log_pencarian_id_log_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.log_pencarian_id_log_seq OWNED BY public.log_pencarian.id_log;


--
-- Name: menu_usaha; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.menu_usaha (
    id_menu integer NOT NULL,
    id_usaha integer NOT NULL,
    nama_menu character varying(60) NOT NULL,
    harga integer NOT NULL,
    CONSTRAINT ck_menu_harga CHECK ((harga >= 0))
);


--
-- Name: menu_usaha_id_menu_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.menu_usaha_id_menu_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: menu_usaha_id_menu_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.menu_usaha_id_menu_seq OWNED BY public.menu_usaha.id_menu;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: pembayaran; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pembayaran (
    id_pembayaran bigint NOT NULL,
    id_pesanan bigint NOT NULL,
    metode character varying(10) NOT NULL,
    jumlah integer NOT NULL,
    referensi character varying(40),
    dibayar_pada timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT ck_bayar_jumlah CHECK ((jumlah >= 0))
);


--
-- Name: pembayaran_id_pembayaran_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pembayaran_id_pembayaran_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pembayaran_id_pembayaran_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pembayaran_id_pembayaran_seq OWNED BY public.pembayaran.id_pembayaran;


--
-- Name: penukaran; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penukaran (
    id_penukaran bigint NOT NULL,
    id_pengguna bigint NOT NULL,
    id_voucher integer NOT NULL,
    poin smallint NOT NULL,
    kode character varying(12) NOT NULL,
    dipakai boolean DEFAULT false NOT NULL,
    waktu timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    dipakai_pada timestamp(0) with time zone
);


--
-- Name: penukaran_id_penukaran_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penukaran_id_penukaran_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penukaran_id_penukaran_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penukaran_id_penukaran_seq OWNED BY public.penukaran.id_penukaran;


--
-- Name: pesanan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pesanan (
    id_pesanan bigint NOT NULL,
    kode character varying(20) NOT NULL,
    id_pengguna bigint,
    nama_pemesan character varying(80) NOT NULL,
    email_pemesan character varying(120) NOT NULL,
    hp_pemesan character varying(20) NOT NULL,
    id_tempat integer NOT NULL,
    tanggal_kunjungan date NOT NULL,
    total integer DEFAULT 0 NOT NULL,
    metode character varying(10) NOT NULL,
    status character varying(12) DEFAULT 'menunggu'::character varying NOT NULL,
    batas_bayar timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    CONSTRAINT ck_pesanan_metode CHECK (((metode)::text = ANY ((ARRAY['qris'::character varying, 'va'::character varying, 'ewallet'::character varying, 'kartu'::character varying])::text[]))),
    CONSTRAINT ck_pesanan_status CHECK (((status)::text = ANY ((ARRAY['menunggu'::character varying, 'lunas'::character varying, 'dibatalkan'::character varying, 'kedaluwarsa'::character varying])::text[]))),
    CONSTRAINT ck_pesanan_tempat CHECK ((id_tempat > 0)),
    CONSTRAINT ck_pesanan_total CHECK ((total >= 0))
);


--
-- Name: pesanan_id_pesanan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pesanan_id_pesanan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pesanan_id_pesanan_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pesanan_id_pesanan_seq OWNED BY public.pesanan.id_pesanan;


--
-- Name: pesanan_item; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pesanan_item (
    id_item bigint NOT NULL,
    id_pesanan bigint NOT NULL,
    jenis_pengunjung character varying(60) NOT NULL,
    harga integer NOT NULL,
    jumlah smallint NOT NULL,
    subtotal integer GENERATED ALWAYS AS ((harga * jumlah)) STORED NOT NULL,
    CONSTRAINT ck_item_harga CHECK ((harga >= 0)),
    CONSTRAINT ck_item_jumlah CHECK (((jumlah >= 1) AND (jumlah <= 20)))
);


--
-- Name: pesanan_item_id_item_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pesanan_item_id_item_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pesanan_item_id_item_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pesanan_item_id_item_seq OWNED BY public.pesanan_item.id_item;


--
-- Name: rencana; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rencana (
    id_rencana bigint NOT NULL,
    kode character varying(12) NOT NULL,
    id_pengguna bigint NOT NULL,
    judul character varying(80) NOT NULL,
    durasi_jam smallint NOT NULL,
    moda_jalan boolean DEFAULT true NOT NULL,
    moda_becak boolean DEFAULT false NOT NULL,
    id_tempat_mulai integer NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    CONSTRAINT ck_rencana_durasi CHECK ((durasi_jam = ANY (ARRAY[2, 4, 6, 8]))),
    CONSTRAINT ck_rencana_moda CHECK ((moda_jalan OR moda_becak))
);


--
-- Name: rencana_id_rencana_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rencana_id_rencana_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rencana_id_rencana_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rencana_id_rencana_seq OWNED BY public.rencana.id_rencana;


--
-- Name: rencana_minat; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rencana_minat (
    id_rencana bigint NOT NULL,
    kode_kategori character varying(20) NOT NULL
);


--
-- Name: ringkasan_ulasan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ringkasan_ulasan (
    id_tempat integer NOT NULL,
    jumlah_ulasan integer DEFAULT 0 NOT NULL,
    rata_rata_bintang numeric(3,2),
    diperbarui_pada timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: riwayat_poin; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.riwayat_poin (
    id_riwayat bigint NOT NULL,
    id_pengguna bigint NOT NULL,
    poin integer NOT NULL,
    sumber character varying(12) NOT NULL,
    id_sumber bigint,
    keterangan character varying(120) NOT NULL,
    waktu timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT ck_poin_sumber CHECK (((sumber)::text = ANY ((ARRAY['checkin'::character varying, 'bonus'::character varying, 'penukaran'::character varying])::text[])))
);


--
-- Name: riwayat_poin_id_riwayat_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.riwayat_poin_id_riwayat_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: riwayat_poin_id_riwayat_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.riwayat_poin_id_riwayat_seq OWNED BY public.riwayat_poin.id_riwayat;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: ulasan; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ulasan (
    id_ulasan bigint NOT NULL,
    id_pengguna bigint NOT NULL,
    id_tempat integer NOT NULL,
    bintang smallint NOT NULL,
    teks character varying(600) NOT NULL,
    status character varying(10) DEFAULT 'menunggu'::character varying NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    CONSTRAINT ck_ulasan_bintang CHECK (((bintang >= 1) AND (bintang <= 5))),
    CONSTRAINT ck_ulasan_status CHECK (((status)::text = ANY ((ARRAY['menunggu'::character varying, 'tayang'::character varying, 'ditolak'::character varying])::text[]))),
    CONSTRAINT ck_ulasan_teks CHECK ((char_length((teks)::text) >= 10))
);


--
-- Name: ulasan_id_ulasan_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ulasan_id_ulasan_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ulasan_id_ulasan_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ulasan_id_ulasan_seq OWNED BY public.ulasan.id_ulasan;


--
-- Name: usaha_mitra; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usaha_mitra (
    id_usaha integer NOT NULL,
    id_pengguna bigint NOT NULL,
    nama_usaha character varying(80) NOT NULL,
    jenis character varying(20) NOT NULL,
    alamat character varying(160) NOT NULL,
    jam character varying(80) NOT NULL,
    deskripsi character varying(400),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    CONSTRAINT ck_usaha_jenis CHECK (((jenis)::text = ANY ((ARRAY['kuliner'::character varying, 'oleh-oleh'::character varying, 'kerajinan'::character varying, 'penginapan'::character varying])::text[])))
);


--
-- Name: usaha_mitra_id_usaha_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usaha_mitra_id_usaha_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usaha_mitra_id_usaha_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usaha_mitra_id_usaha_seq OWNED BY public.usaha_mitra.id_usaha;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    peran character varying(12) DEFAULT 'wisatawan'::character varying NOT NULL,
    no_hp character varying(20),
    CONSTRAINT ck_users_peran CHECK (((peran)::text = ANY ((ARRAY['wisatawan'::character varying, 'mitra'::character varying, 'kurator'::character varying])::text[])))
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: v_celah_konten; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_celah_konten AS
 SELECT kata_kunci,
    count(*) AS jumlah_dicari,
    max(waktu) AS terakhir_dicari
   FROM public.log_pencarian
  WHERE (jumlah_hasil = 0)
  GROUP BY kata_kunci;


--
-- Name: v_penjualan_tiket; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_penjualan_tiket AS
 SELECT p.id_tempat,
    (date_trunc('month'::text, (p.tanggal_kunjungan)::timestamp with time zone))::date AS bulan,
    count(*) AS jumlah_pesanan,
    sum(i.jumlah_orang) AS jumlah_pengunjung,
    sum(p.total) AS pendapatan
   FROM (public.pesanan p
     JOIN ( SELECT pesanan_item.id_pesanan,
            sum(pesanan_item.jumlah) AS jumlah_orang
           FROM public.pesanan_item
          GROUP BY pesanan_item.id_pesanan) i ON ((i.id_pesanan = p.id_pesanan)))
  WHERE ((p.status)::text = 'lunas'::text)
  GROUP BY p.id_tempat, (date_trunc('month'::text, (p.tanggal_kunjungan)::timestamp with time zone));


--
-- Name: v_saldo_poin; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.v_saldo_poin AS
 SELECT u.id AS id_pengguna,
    u.name AS nama,
    u.email,
    COALESCE(sum(r.poin) FILTER (WHERE (r.poin > 0)), (0)::bigint) AS poin_didapat,
    COALESCE((- sum(r.poin) FILTER (WHERE (r.poin < 0))), (0)::bigint) AS poin_dipakai,
    COALESCE(sum(r.poin), (0)::bigint) AS saldo
   FROM (public.users u
     LEFT JOIN public.riwayat_poin r ON ((r.id_pengguna = u.id)))
  WHERE ((u.peran)::text = 'wisatawan'::text)
  GROUP BY u.id, u.name, u.email;


--
-- Name: voucher; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.voucher (
    id_voucher integer NOT NULL,
    id_usaha integer NOT NULL,
    nama character varying(60) NOT NULL,
    poin smallint NOT NULL,
    stok smallint NOT NULL,
    stok_awal smallint NOT NULL,
    berlaku_sampai date NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    CONSTRAINT ck_voucher_poin CHECK (((poin >= 10) AND (poin <= 500))),
    CONSTRAINT ck_voucher_stok CHECK (((stok >= 0) AND (stok <= stok_awal)))
);


--
-- Name: voucher_id_voucher_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.voucher_id_voucher_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: voucher_id_voucher_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.voucher_id_voucher_seq OWNED BY public.voucher.id_voucher;


--
-- Name: checkin id_checkin; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkin ALTER COLUMN id_checkin SET DEFAULT nextval('public.checkin_id_checkin_seq'::regclass);


--
-- Name: e_tiket id_etiket; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.e_tiket ALTER COLUMN id_etiket SET DEFAULT nextval('public.e_tiket_id_etiket_seq'::regclass);


--
-- Name: log_pencarian id_log; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.log_pencarian ALTER COLUMN id_log SET DEFAULT nextval('public.log_pencarian_id_log_seq'::regclass);


--
-- Name: menu_usaha id_menu; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.menu_usaha ALTER COLUMN id_menu SET DEFAULT nextval('public.menu_usaha_id_menu_seq'::regclass);


--
-- Name: pembayaran id_pembayaran; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pembayaran ALTER COLUMN id_pembayaran SET DEFAULT nextval('public.pembayaran_id_pembayaran_seq'::regclass);


--
-- Name: penukaran id_penukaran; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penukaran ALTER COLUMN id_penukaran SET DEFAULT nextval('public.penukaran_id_penukaran_seq'::regclass);


--
-- Name: pesanan id_pesanan; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesanan ALTER COLUMN id_pesanan SET DEFAULT nextval('public.pesanan_id_pesanan_seq'::regclass);


--
-- Name: pesanan_item id_item; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesanan_item ALTER COLUMN id_item SET DEFAULT nextval('public.pesanan_item_id_item_seq'::regclass);


--
-- Name: rencana id_rencana; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rencana ALTER COLUMN id_rencana SET DEFAULT nextval('public.rencana_id_rencana_seq'::regclass);


--
-- Name: riwayat_poin id_riwayat; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.riwayat_poin ALTER COLUMN id_riwayat SET DEFAULT nextval('public.riwayat_poin_id_riwayat_seq'::regclass);


--
-- Name: ulasan id_ulasan; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ulasan ALTER COLUMN id_ulasan SET DEFAULT nextval('public.ulasan_id_ulasan_seq'::regclass);


--
-- Name: usaha_mitra id_usaha; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usaha_mitra ALTER COLUMN id_usaha SET DEFAULT nextval('public.usaha_mitra_id_usaha_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: voucher id_voucher; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voucher ALTER COLUMN id_voucher SET DEFAULT nextval('public.voucher_id_voucher_seq'::regclass);


--
-- Data for Name: checkin; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.checkin (id_checkin, id_pengguna, id_tempat, jarak_m, tanggal, waktu) VALUES
	(1, 1, 1, 42, '2026-11-06', '2026-11-06 21:43:39+07'),
	(2, 1, 3, 18, '2026-11-06', '2026-11-06 21:43:39+07');


--
-- Data for Name: e_tiket; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.e_tiket (id_etiket, id_pesanan, kode_qr, diterbitkan_pada, dipakai_pada) VALUES
	(1, 1, 'QR-39D591C36C37', '2026-11-06 21:43:39+07', NULL);


--
-- Data for Name: log_pencarian; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.log_pencarian (id_log, kata_kunci, jumlah_hasil, waktu) VALUES
	(1, 'istana maimun', 3, '2026-11-06 21:43:39+07'),
	(2, 'kebun binatang', 0, '2026-11-06 21:43:39+07'),
	(3, 'kebun binatang', 0, '2026-11-06 21:43:39+07');


--
-- Data for Name: menu_usaha; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.menu_usaha (id_menu, id_usaha, nama_menu, harga) VALUES
	(1, 1, 'Bika ambon potong', 8000),
	(2, 1, 'Teh manis', 5000);


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: pembayaran; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.pembayaran (id_pembayaran, id_pesanan, metode, jumlah, referensi, dibayar_pada) VALUES
	(1, 1, 'qris', 25000, 'UJI-COBA', '2026-11-06 21:43:39+07');


--
-- Data for Name: penukaran; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.penukaran (id_penukaran, id_pengguna, id_voucher, poin, kode, dipakai, waktu, dipakai_pada) VALUES
	(1, 1, 1, 30, 'V-C1F671', false, '2026-11-06 21:43:39+07', NULL);


--
-- Data for Name: pesanan; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.pesanan (id_pesanan, kode, id_pengguna, nama_pemesan, email_pemesan, hp_pemesan, id_tempat, tanggal_kunjungan, total, metode, status, batas_bayar, created_at, updated_at) VALUES
	(1, 'MV-A82086AB', 1, 'Rina', 'rina@contoh.id', '081200000000', 1, '2026-11-07', 25000, 'qris', 'lunas', '2026-11-06 21:58:39+07', '2026-11-06 21:43:39+07', '2026-11-06 21:43:39+07'),
	(2, 'MV-82FD5AB0', 1, 'Rina', 'rina@contoh.id', '081200000000', 3, '2026-11-08', 35000, 'va', 'menunggu', '2026-11-06 21:58:39+07', '2026-11-06 21:43:39+07', '2026-11-06 21:43:39+07');


--
-- Data for Name: pesanan_item; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.pesanan_item (id_item, id_pesanan, jenis_pengunjung, harga, jumlah) VALUES
	(1, 1, 'Dewasa', 10000, 2),
	(2, 1, 'Anak', 5000, 1),
	(3, 2, 'Umum, termasuk pemandu', 35000, 1);


--
-- Data for Name: rencana; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.rencana (id_rencana, kode, id_pengguna, judul, durasi_jam, moda_jalan, moda_becak, id_tempat_mulai, created_at, updated_at) VALUES
	(1, 'mdn-uji1', 1, 'Setengah hari di kota lama', 4, true, true, 1, '2026-11-06 14:43:39+07', '2026-11-06 14:43:39+07');


--
-- Data for Name: rencana_minat; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.rencana_minat (id_rencana, kode_kategori) VALUES
	(1, 'sejarah'),
	(1, 'kuliner');


--
-- Data for Name: ringkasan_ulasan; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.ringkasan_ulasan (id_tempat, jumlah_ulasan, rata_rata_bintang, diperbarui_pada) VALUES
	(1, 1, 5.00, '2026-11-06 21:43:39+07'),
	(3, 0, NULL, '2026-11-06 21:43:39+07');


--
-- Data for Name: riwayat_poin; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.riwayat_poin (id_riwayat, id_pengguna, poin, sumber, id_sumber, keterangan, waktu) VALUES
	(1, 1, 10, 'checkin', 1, 'Check-in di tempat #1', '2026-11-06 21:43:39+07'),
	(2, 1, 10, 'checkin', 2, 'Check-in di tempat #3', '2026-11-06 21:43:39+07'),
	(3, 1, 25, 'bonus', NULL, 'Badge Pemburu Sejarah: 2 tempat bersejarah', '2026-11-06 21:43:39+07'),
	(4, 1, -30, 'penukaran', 1, 'Tukar voucher V-C1F671', '2026-11-06 21:43:39+07');


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: ulasan; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.ulasan (id_ulasan, id_pengguna, id_tempat, bintang, teks, status, created_at, updated_at) VALUES
	(1, 1, 1, 5, 'Ulasan uji coba: istananya terawat dan pemandunya ramah.', 'tayang', '2026-11-06 14:43:39+07', '2026-11-06 14:43:39+07'),
	(2, 1, 3, 4, 'Ulasan uji coba yang masih menunggu moderasi.', 'menunggu', '2026-11-06 14:43:39+07', '2026-11-06 14:43:39+07');


--
-- Data for Name: usaha_mitra; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.usaha_mitra (id_usaha, id_pengguna, nama_usaha, jenis, alamat, jam, deskripsi, created_at, updated_at) VALUES
	(1, 2, 'Kedai Contoh MedanVisit', 'kuliner', 'Alamat contoh, Medan', 'Setiap hari, 08.00–21.00', 'Usaha uji coba untuk menguji fitur mitra.', '2026-11-06 14:43:39+07', '2026-11-06 14:43:39+07');


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, peran, no_hp) VALUES
	(1, 'Rina', 'rina@contoh.id', NULL, '$2y$12$ThvWT8D4FVNjY7YRBkVq6eqmSwFC56Lg0.gJ8WaopFeTEytEJoYwW', NULL, '2026-11-06 14:43:39', '2026-11-06 14:43:39', 'wisatawan', NULL),
	(2, 'Usaha Contoh', 'usaha@contoh.id', NULL, '$2y$12$ThvWT8D4FVNjY7YRBkVq6eqmSwFC56Lg0.gJ8WaopFeTEytEJoYwW', NULL, '2026-11-06 14:43:39', '2026-11-06 14:43:39', 'mitra', NULL),
	(3, 'Kurator MedanVisit', 'kurator@contoh.id', NULL, '$2y$12$ThvWT8D4FVNjY7YRBkVq6eqmSwFC56Lg0.gJ8WaopFeTEytEJoYwW', NULL, '2026-11-06 14:43:39', '2026-11-06 14:43:39', 'kurator', NULL);


--
-- Data for Name: voucher; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.voucher (id_voucher, id_usaha, nama, poin, stok, stok_awal, berlaku_sampai, created_at, updated_at) VALUES
	(1, 1, 'Diskon 10% menu kedai', 30, 19, 20, '2027-02-06', '2026-11-06 14:43:39+07', '2026-11-06 21:43:39+07');


--
-- Name: checkin_id_checkin_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.checkin_id_checkin_seq', 2, true);


--
-- Name: e_tiket_id_etiket_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.e_tiket_id_etiket_seq', 1, true);


--
-- Name: log_pencarian_id_log_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.log_pencarian_id_log_seq', 3, true);


--
-- Name: menu_usaha_id_menu_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.menu_usaha_id_menu_seq', 2, true);


--
-- Name: pembayaran_id_pembayaran_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pembayaran_id_pembayaran_seq', 1, true);


--
-- Name: penukaran_id_penukaran_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.penukaran_id_penukaran_seq', 1, true);


--
-- Name: pesanan_id_pesanan_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pesanan_id_pesanan_seq', 2, true);


--
-- Name: pesanan_item_id_item_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pesanan_item_id_item_seq', 3, true);


--
-- Name: rencana_id_rencana_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.rencana_id_rencana_seq', 1, true);


--
-- Name: riwayat_poin_id_riwayat_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.riwayat_poin_id_riwayat_seq', 4, true);


--
-- Name: ulasan_id_ulasan_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ulasan_id_ulasan_seq', 2, true);


--
-- Name: usaha_mitra_id_usaha_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.usaha_mitra_id_usaha_seq', 1, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 3, true);


--
-- Name: voucher_id_voucher_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.voucher_id_voucher_seq', 1, true);


--
-- Name: checkin checkin_id_pengguna_id_tempat_tanggal_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkin
    ADD CONSTRAINT checkin_id_pengguna_id_tempat_tanggal_unique UNIQUE (id_pengguna, id_tempat, tanggal);


--
-- Name: checkin checkin_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkin
    ADD CONSTRAINT checkin_pkey PRIMARY KEY (id_checkin);


--
-- Name: e_tiket e_tiket_id_pesanan_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.e_tiket
    ADD CONSTRAINT e_tiket_id_pesanan_unique UNIQUE (id_pesanan);


--
-- Name: e_tiket e_tiket_kode_qr_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.e_tiket
    ADD CONSTRAINT e_tiket_kode_qr_unique UNIQUE (kode_qr);


--
-- Name: e_tiket e_tiket_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.e_tiket
    ADD CONSTRAINT e_tiket_pkey PRIMARY KEY (id_etiket);


--
-- Name: log_pencarian log_pencarian_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.log_pencarian
    ADD CONSTRAINT log_pencarian_pkey PRIMARY KEY (id_log);


--
-- Name: menu_usaha menu_usaha_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.menu_usaha
    ADD CONSTRAINT menu_usaha_pkey PRIMARY KEY (id_menu);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: pembayaran pembayaran_id_pesanan_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pembayaran
    ADD CONSTRAINT pembayaran_id_pesanan_unique UNIQUE (id_pesanan);


--
-- Name: pembayaran pembayaran_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pembayaran
    ADD CONSTRAINT pembayaran_pkey PRIMARY KEY (id_pembayaran);


--
-- Name: penukaran penukaran_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penukaran
    ADD CONSTRAINT penukaran_kode_unique UNIQUE (kode);


--
-- Name: penukaran penukaran_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penukaran
    ADD CONSTRAINT penukaran_pkey PRIMARY KEY (id_penukaran);


--
-- Name: pesanan_item pesanan_item_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesanan_item
    ADD CONSTRAINT pesanan_item_pkey PRIMARY KEY (id_item);


--
-- Name: pesanan pesanan_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesanan
    ADD CONSTRAINT pesanan_kode_unique UNIQUE (kode);


--
-- Name: pesanan pesanan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesanan
    ADD CONSTRAINT pesanan_pkey PRIMARY KEY (id_pesanan);


--
-- Name: rencana rencana_kode_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rencana
    ADD CONSTRAINT rencana_kode_unique UNIQUE (kode);


--
-- Name: rencana_minat rencana_minat_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rencana_minat
    ADD CONSTRAINT rencana_minat_pkey PRIMARY KEY (id_rencana, kode_kategori);


--
-- Name: rencana rencana_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rencana
    ADD CONSTRAINT rencana_pkey PRIMARY KEY (id_rencana);


--
-- Name: ringkasan_ulasan ringkasan_ulasan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ringkasan_ulasan
    ADD CONSTRAINT ringkasan_ulasan_pkey PRIMARY KEY (id_tempat);


--
-- Name: riwayat_poin riwayat_poin_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.riwayat_poin
    ADD CONSTRAINT riwayat_poin_pkey PRIMARY KEY (id_riwayat);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: ulasan ulasan_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ulasan
    ADD CONSTRAINT ulasan_pkey PRIMARY KEY (id_ulasan);


--
-- Name: usaha_mitra usaha_mitra_id_pengguna_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usaha_mitra
    ADD CONSTRAINT usaha_mitra_id_pengguna_unique UNIQUE (id_pengguna);


--
-- Name: usaha_mitra usaha_mitra_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usaha_mitra
    ADD CONSTRAINT usaha_mitra_pkey PRIMARY KEY (id_usaha);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: voucher voucher_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voucher
    ADD CONSTRAINT voucher_pkey PRIMARY KEY (id_voucher);


--
-- Name: ix_checkin_tempat; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_checkin_tempat ON public.checkin USING btree (id_tempat);


--
-- Name: ix_log_tanpa_hasil; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_log_tanpa_hasil ON public.log_pencarian USING btree (kata_kunci) WHERE (jumlah_hasil = 0);


--
-- Name: ix_penukaran_pengguna; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_penukaran_pengguna ON public.penukaran USING btree (id_pengguna);


--
-- Name: ix_pesanan_menunggu; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_pesanan_menunggu ON public.pesanan USING btree (batas_bayar) WHERE ((status)::text = 'menunggu'::text);


--
-- Name: ix_pesanan_pengguna; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_pesanan_pengguna ON public.pesanan USING btree (id_pengguna);


--
-- Name: ix_pesanan_tempat; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_pesanan_tempat ON public.pesanan USING btree (id_tempat, tanggal_kunjungan);


--
-- Name: ix_riwayat_pengguna; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_riwayat_pengguna ON public.riwayat_poin USING btree (id_pengguna);


--
-- Name: ix_ulasan_tempat; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ix_ulasan_tempat ON public.ulasan USING btree (id_tempat, status);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: checkin checkin_poin; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER checkin_poin AFTER INSERT ON public.checkin FOR EACH ROW EXECUTE FUNCTION public.trg_checkin_poin();


--
-- Name: penukaran penukaran_cek; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER penukaran_cek BEFORE INSERT ON public.penukaran FOR EACH ROW EXECUTE FUNCTION public.trg_penukaran_cek();


--
-- Name: penukaran penukaran_poin; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER penukaran_poin AFTER INSERT ON public.penukaran FOR EACH ROW EXECUTE FUNCTION public.trg_penukaran_poin();


--
-- Name: pesanan_item pesanan_item_total; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER pesanan_item_total AFTER INSERT OR DELETE OR UPDATE ON public.pesanan_item FOR EACH ROW EXECUTE FUNCTION public.trg_pesanan_hitung_total();


--
-- Name: ulasan ulasan_ringkasan; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER ulasan_ringkasan AFTER INSERT OR DELETE OR UPDATE OF status, bintang, id_tempat ON public.ulasan FOR EACH ROW EXECUTE FUNCTION public.trg_ulasan_ringkasan();


--
-- Name: checkin checkin_id_pengguna_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkin
    ADD CONSTRAINT checkin_id_pengguna_foreign FOREIGN KEY (id_pengguna) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: e_tiket e_tiket_id_pesanan_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.e_tiket
    ADD CONSTRAINT e_tiket_id_pesanan_foreign FOREIGN KEY (id_pesanan) REFERENCES public.pesanan(id_pesanan) ON DELETE CASCADE;


--
-- Name: menu_usaha menu_usaha_id_usaha_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.menu_usaha
    ADD CONSTRAINT menu_usaha_id_usaha_foreign FOREIGN KEY (id_usaha) REFERENCES public.usaha_mitra(id_usaha) ON DELETE CASCADE;


--
-- Name: pembayaran pembayaran_id_pesanan_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pembayaran
    ADD CONSTRAINT pembayaran_id_pesanan_foreign FOREIGN KEY (id_pesanan) REFERENCES public.pesanan(id_pesanan) ON DELETE CASCADE;


--
-- Name: penukaran penukaran_id_pengguna_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penukaran
    ADD CONSTRAINT penukaran_id_pengguna_foreign FOREIGN KEY (id_pengguna) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: penukaran penukaran_id_voucher_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penukaran
    ADD CONSTRAINT penukaran_id_voucher_foreign FOREIGN KEY (id_voucher) REFERENCES public.voucher(id_voucher) ON DELETE RESTRICT;


--
-- Name: pesanan pesanan_id_pengguna_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesanan
    ADD CONSTRAINT pesanan_id_pengguna_foreign FOREIGN KEY (id_pengguna) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pesanan_item pesanan_item_id_pesanan_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pesanan_item
    ADD CONSTRAINT pesanan_item_id_pesanan_foreign FOREIGN KEY (id_pesanan) REFERENCES public.pesanan(id_pesanan) ON DELETE CASCADE;


--
-- Name: rencana rencana_id_pengguna_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rencana
    ADD CONSTRAINT rencana_id_pengguna_foreign FOREIGN KEY (id_pengguna) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: rencana_minat rencana_minat_id_rencana_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rencana_minat
    ADD CONSTRAINT rencana_minat_id_rencana_foreign FOREIGN KEY (id_rencana) REFERENCES public.rencana(id_rencana) ON DELETE CASCADE;


--
-- Name: riwayat_poin riwayat_poin_id_pengguna_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.riwayat_poin
    ADD CONSTRAINT riwayat_poin_id_pengguna_foreign FOREIGN KEY (id_pengguna) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ulasan ulasan_id_pengguna_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ulasan
    ADD CONSTRAINT ulasan_id_pengguna_foreign FOREIGN KEY (id_pengguna) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: usaha_mitra usaha_mitra_id_pengguna_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usaha_mitra
    ADD CONSTRAINT usaha_mitra_id_pengguna_foreign FOREIGN KEY (id_pengguna) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: voucher voucher_id_usaha_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voucher
    ADD CONSTRAINT voucher_id_usaha_foreign FOREIGN KEY (id_usaha) REFERENCES public.usaha_mitra(id_usaha) ON DELETE CASCADE;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: -
--

GRANT USAGE ON SCHEMA public TO medanvisit_aplikasi;
GRANT USAGE ON SCHEMA public TO medanvisit_laporan;


--
-- Name: PROCEDURE sp_bayar_pesanan(IN p_kode character varying, IN p_referensi character varying, INOUT p_kode_qr character varying); Type: ACL; Schema: public; Owner: -
--

GRANT ALL ON PROCEDURE public.sp_bayar_pesanan(IN p_kode character varying, IN p_referensi character varying, INOUT p_kode_qr character varying) TO medanvisit_aplikasi;


--
-- Name: PROCEDURE sp_buat_pesanan(IN p_id_pengguna bigint, IN p_nama character varying, IN p_email character varying, IN p_hp character varying, IN p_id_tempat integer, IN p_tanggal date, IN p_metode character varying, IN p_item jsonb, INOUT p_kode character varying); Type: ACL; Schema: public; Owner: -
--

GRANT ALL ON PROCEDURE public.sp_buat_pesanan(IN p_id_pengguna bigint, IN p_nama character varying, IN p_email character varying, IN p_hp character varying, IN p_id_tempat integer, IN p_tanggal date, IN p_metode character varying, IN p_item jsonb, INOUT p_kode character varying) TO medanvisit_aplikasi;


--
-- Name: PROCEDURE sp_kedaluwarsakan_pesanan(); Type: ACL; Schema: public; Owner: -
--

GRANT ALL ON PROCEDURE public.sp_kedaluwarsakan_pesanan() TO medanvisit_aplikasi;


--
-- Name: TABLE checkin; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.checkin TO medanvisit_aplikasi;


--
-- Name: SEQUENCE checkin_id_checkin_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.checkin_id_checkin_seq TO medanvisit_aplikasi;


--
-- Name: TABLE e_tiket; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.e_tiket TO medanvisit_aplikasi;


--
-- Name: SEQUENCE e_tiket_id_etiket_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.e_tiket_id_etiket_seq TO medanvisit_aplikasi;


--
-- Name: TABLE log_pencarian; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.log_pencarian TO medanvisit_aplikasi;


--
-- Name: SEQUENCE log_pencarian_id_log_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.log_pencarian_id_log_seq TO medanvisit_aplikasi;


--
-- Name: TABLE menu_usaha; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.menu_usaha TO medanvisit_aplikasi;


--
-- Name: SEQUENCE menu_usaha_id_menu_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.menu_usaha_id_menu_seq TO medanvisit_aplikasi;


--
-- Name: TABLE password_reset_tokens; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.password_reset_tokens TO medanvisit_aplikasi;


--
-- Name: TABLE pembayaran; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.pembayaran TO medanvisit_aplikasi;


--
-- Name: SEQUENCE pembayaran_id_pembayaran_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.pembayaran_id_pembayaran_seq TO medanvisit_aplikasi;


--
-- Name: TABLE penukaran; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.penukaran TO medanvisit_aplikasi;


--
-- Name: SEQUENCE penukaran_id_penukaran_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.penukaran_id_penukaran_seq TO medanvisit_aplikasi;


--
-- Name: TABLE pesanan; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.pesanan TO medanvisit_aplikasi;


--
-- Name: SEQUENCE pesanan_id_pesanan_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.pesanan_id_pesanan_seq TO medanvisit_aplikasi;


--
-- Name: TABLE pesanan_item; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.pesanan_item TO medanvisit_aplikasi;


--
-- Name: SEQUENCE pesanan_item_id_item_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.pesanan_item_id_item_seq TO medanvisit_aplikasi;


--
-- Name: TABLE rencana; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.rencana TO medanvisit_aplikasi;


--
-- Name: SEQUENCE rencana_id_rencana_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.rencana_id_rencana_seq TO medanvisit_aplikasi;


--
-- Name: TABLE rencana_minat; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.rencana_minat TO medanvisit_aplikasi;


--
-- Name: TABLE ringkasan_ulasan; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.ringkasan_ulasan TO medanvisit_aplikasi;
GRANT SELECT ON TABLE public.ringkasan_ulasan TO medanvisit_laporan;


--
-- Name: TABLE riwayat_poin; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.riwayat_poin TO medanvisit_aplikasi;


--
-- Name: SEQUENCE riwayat_poin_id_riwayat_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.riwayat_poin_id_riwayat_seq TO medanvisit_aplikasi;


--
-- Name: TABLE sessions; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.sessions TO medanvisit_aplikasi;


--
-- Name: TABLE ulasan; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.ulasan TO medanvisit_aplikasi;


--
-- Name: SEQUENCE ulasan_id_ulasan_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.ulasan_id_ulasan_seq TO medanvisit_aplikasi;


--
-- Name: TABLE usaha_mitra; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.usaha_mitra TO medanvisit_aplikasi;


--
-- Name: SEQUENCE usaha_mitra_id_usaha_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.usaha_mitra_id_usaha_seq TO medanvisit_aplikasi;


--
-- Name: TABLE users; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.users TO medanvisit_aplikasi;


--
-- Name: SEQUENCE users_id_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.users_id_seq TO medanvisit_aplikasi;


--
-- Name: TABLE v_celah_konten; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.v_celah_konten TO medanvisit_aplikasi;
GRANT SELECT ON TABLE public.v_celah_konten TO medanvisit_laporan;


--
-- Name: TABLE v_penjualan_tiket; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.v_penjualan_tiket TO medanvisit_aplikasi;
GRANT SELECT ON TABLE public.v_penjualan_tiket TO medanvisit_laporan;


--
-- Name: TABLE v_saldo_poin; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.v_saldo_poin TO medanvisit_aplikasi;
GRANT SELECT ON TABLE public.v_saldo_poin TO medanvisit_laporan;


--
-- Name: TABLE voucher; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.voucher TO medanvisit_aplikasi;


--
-- Name: SEQUENCE voucher_id_voucher_seq; Type: ACL; Schema: public; Owner: -
--

GRANT SELECT,USAGE ON SEQUENCE public.voucher_id_voucher_seq TO medanvisit_aplikasi;


--
-- PostgreSQL database dump complete
--

