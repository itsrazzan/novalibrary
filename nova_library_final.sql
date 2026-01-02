--
-- PostgreSQL database dump
--

\restrict Xh85XZA1I60XEycx8xbPUIXPKEsmnZSOfE0BVGphM9fJaJLUCympuVwwGdoA2ra

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: get_user_for_auth(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.get_user_for_auth(p_username character varying) RETURNS TABLE(id integer, username character varying, hashed_password character varying, status character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
     RETURN QUERY
     SELECT u.id, u.username, u.password, u.status
     FROM username u
     WHERE u.username = p_username;
END;
$$;


ALTER FUNCTION public.get_user_for_auth(p_username character varying) OWNER TO postgres;

--
-- Name: insert_penalty(integer, integer); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.insert_penalty(IN p_user_id integer, IN p_amount integer)
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO penalty (id, large_fines)
    VALUES (p_user_id, p_amount);
    RAISE NOTICE 'Denda sebesar % berhasil dicatat untuk user ID %', p_amount, p_user_id;
END;
$$;


ALTER PROCEDURE public.insert_penalty(IN p_user_id integer, IN p_amount integer) OWNER TO postgres;

--
-- Name: login_user_with_role(character varying, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.login_user_with_role(p_username character varying, p_password character varying) RETURNS TABLE(user_id integer, username character varying, role character varying)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        u.id,
        u.username,
        u.status
    FROM username u
    WHERE u.username = p_username
      AND u.password = p_password;
END;
$$;


ALTER FUNCTION public.login_user_with_role(p_username character varying, p_password character varying) OWNER TO postgres;

--
-- Name: proses_kembali_dan_denda(integer, date); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.proses_kembali_dan_denda(IN p_loan_id integer, IN p_return_date date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_due_date DATE;
    v_user_id INT;
    v_late_days INT;
BEGIN
    -- Ambil info jatuh tempo
    SELECT due_date, id INTO v_due_date, v_user_id 
    FROM booklending WHERE loan_id = p_loan_id;

    v_late_days := p_return_date - v_due_date;

    -- Jika telat, masukkan ke tabel penalty
    IF v_late_days > 0 THEN
        INSERT INTO penalty (id, large_fines) 
        VALUES (v_user_id, v_late_days * 5000); -- Denda 5rb/hari
    END IF;

    -- Masukkan ke tabel bookreturn (sesuai ERD kamu)
    INSERT INTO bookreturn (loan_id, return_date) 
    VALUES (p_loan_id, p_return_date);
END;
$$;


ALTER PROCEDURE public.proses_kembali_dan_denda(IN p_loan_id integer, IN p_return_date date) OWNER TO postgres;

--
-- Name: update_book_status(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_book_status() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
begin
if tg_op = 'insert' then
update book set book_status = false
where book_id = new.nook_id;
end if;

if tg_op = 'update' and new.return_date is not null then
update book set book_status = true
where book_id = new.book_id;
end if;
return new;
end;
$$;


ALTER FUNCTION public.update_book_status() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: book; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.book (
    book_id integer NOT NULL,
    category_id integer NOT NULL,
    book_title character varying(100) NOT NULL,
    author character varying(100),
    publisher character varying(255),
    published_year date,
    image_path character varying(255),
    book_status boolean DEFAULT true,
    sinopsis text
);


ALTER TABLE public.book OWNER TO postgres;

--
-- Name: bookcategory; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bookcategory (
    category_id integer NOT NULL,
    category_name character varying(100) NOT NULL,
    explanation text
);


ALTER TABLE public.bookcategory OWNER TO postgres;

--
-- Name: booklending; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booklending (
    loan_id integer NOT NULL,
    book_id integer NOT NULL,
    loan_date date DEFAULT CURRENT_DATE NOT NULL,
    due_date date NOT NULL,
    return_date date,
    id integer,
    CONSTRAINT check_loan_date CHECK ((loan_date <= due_date))
);


ALTER TABLE public.booklending OWNER TO postgres;

--
-- Name: bookreturn; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bookreturn (
    return_id integer NOT NULL,
    loan_id integer NOT NULL,
    return_date date NOT NULL,
    penalty_id integer
);


ALTER TABLE public.bookreturn OWNER TO postgres;

--
-- Name: penalty; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.penalty (
    penalty_id integer NOT NULL,
    id integer NOT NULL,
    large_fines integer NOT NULL
);


ALTER TABLE public.penalty OWNER TO postgres;

--
-- Name: username; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.username (
    id integer NOT NULL,
    username character varying(20) NOT NULL,
    password character varying(255),
    status character varying(100),
    name character varying(100),
    email character varying(100),
    phone_number character varying(20),
    google_id character varying(255),
    auth_provider character varying(20) DEFAULT 'local'::character varying
);


ALTER TABLE public.username OWNER TO postgres;

--
-- Name: mv_rekap_denda; Type: MATERIALIZED VIEW; Schema: public; Owner: postgres
--

CREATE MATERIALIZED VIEW public.mv_rekap_denda AS
 SELECT u.name,
    sum(p.large_fines) AS total_denda
   FROM (public.username u
     JOIN public.penalty p ON ((u.id = p.id)))
  GROUP BY u.name
  WITH NO DATA;


ALTER MATERIALIZED VIEW public.mv_rekap_denda OWNER TO postgres;

--
-- Name: mv_rekap_denda_member; Type: MATERIALIZED VIEW; Schema: public; Owner: postgres
--

CREATE MATERIALIZED VIEW public.mv_rekap_denda_member AS
 SELECT u.name,
    sum(p.large_fines) AS total_denda
   FROM (public.username u
     JOIN public.penalty p ON ((u.id = p.id)))
  GROUP BY u.name
  WITH NO DATA;


ALTER MATERIALIZED VIEW public.mv_rekap_denda_member OWNER TO postgres;

--
-- Name: mv_statistik_member; Type: MATERIALIZED VIEW; Schema: public; Owner: postgres
--

CREATE MATERIALIZED VIEW public.mv_statistik_member AS
 SELECT u.username,
    u.name,
    count(l.loan_id) AS total_pinjaman
   FROM (public.username u
     LEFT JOIN public.booklending l ON ((u.id = l.id)))
  GROUP BY u.username, u.name
  WITH NO DATA;


ALTER MATERIALIZED VIEW public.mv_statistik_member OWNER TO postgres;

--
-- Name: mv_total_denda; Type: MATERIALIZED VIEW; Schema: public; Owner: postgres
--

CREATE MATERIALIZED VIEW public.mv_total_denda AS
 SELECT u.name,
    sum(p.large_fines) AS total_bayar
   FROM (public.penalty p
     JOIN public.username u ON ((p.id = u.id)))
  GROUP BY u.name
  WITH NO DATA;


ALTER MATERIALIZED VIEW public.mv_total_denda OWNER TO postgres;

--
-- Name: penalty_penalty_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.penalty_penalty_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.penalty_penalty_id_seq OWNER TO postgres;

--
-- Name: penalty_penalty_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.penalty_penalty_id_seq OWNED BY public.penalty.penalty_id;


--
-- Name: view_buku_populer; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.view_buku_populer AS
 SELECT b.book_title,
    count(l.loan_id) AS total_dipinjam
   FROM (public.book b
     JOIN public.booklending l ON ((b.book_id = l.book_id)))
  GROUP BY b.book_title
  ORDER BY (count(l.loan_id)) DESC;


ALTER VIEW public.view_buku_populer OWNER TO postgres;

--
-- Name: view_peminjaman_aktif; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.view_peminjaman_aktif AS
 SELECT b.book_title,
    u.name,
    l.loan_date,
    l.due_date
   FROM ((public.booklending l
     JOIN public.book b ON ((l.book_id = b.book_id)))
     JOIN public.username u ON ((l.id = u.id)))
  WHERE (((u.username)::text = CURRENT_USER) AND (l.return_date IS NULL));


ALTER VIEW public.view_peminjaman_aktif OWNER TO postgres;

--
-- Name: view_riwayat_lengkap; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.view_riwayat_lengkap AS
 SELECT u.name,
    b.book_title,
    l.loan_date,
    r.return_date
   FROM (((public.booklending l
     JOIN public.book b ON ((l.book_id = b.book_id)))
     JOIN public.username u ON ((l.id = u.id)))
     LEFT JOIN public.bookreturn r ON ((l.loan_id = r.loan_id)));


ALTER VIEW public.view_riwayat_lengkap OWNER TO postgres;

--
-- Name: waiting_list; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.waiting_list (
    waiting_id integer NOT NULL,
    book_id integer NOT NULL,
    id integer NOT NULL,
    request_date date DEFAULT CURRENT_DATE NOT NULL
);


ALTER TABLE public.waiting_list OWNER TO postgres;

--
-- Name: waiting_list_waiting_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.waiting_list_waiting_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.waiting_list_waiting_id_seq OWNER TO postgres;

--
-- Name: waiting_list_waiting_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.waiting_list_waiting_id_seq OWNED BY public.waiting_list.waiting_id;


--
-- Name: penalty penalty_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penalty ALTER COLUMN penalty_id SET DEFAULT nextval('public.penalty_penalty_id_seq'::regclass);


--
-- Name: waiting_list waiting_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.waiting_list ALTER COLUMN waiting_id SET DEFAULT nextval('public.waiting_list_waiting_id_seq'::regclass);


--
-- Data for Name: book; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.book (book_id, category_id, book_title, author, publisher, published_year, image_path, book_status, sinopsis) FROM stdin;
2	1	Sang Pemimpi	Andrea Hirata	Bentang Pustaka	2006-01-01	public/img/books/sang_pemimpi.jpeg	t	\N
3	1	Edensor	Andrea Hirata	Bentang Pustaka	2007-01-01	public/img/books/edensor.jpeg	t	\N
4	1	Maryamah Karpov	Andrea Hirata	Bentang Pustaka	2008-01-01	public/img/books/maryamah_karpov.jpeg	t	\N
5	1	Perahu Kertas	Dee Lestari	Bentang Pustaka	2009-01-01	public/img/books/perahu_kertas.jpeg	t	\N
6	1	Supernova	Dee Lestari	Truedee Pustaka	2001-01-01	public/img/books/supernova.jpeg	t	\N
7	1	Rectoverso	Dee Lestari	Bentang Pustaka	2008-01-01	public/img/books/rectoverso.jpeg	t	\N
8	1	Ayat-Ayat Cinta	Habiburrahman El Shirazy	Republika	2004-01-01	public/img/books/ayat_ayat_cinta.jpeg	t	\N
9	1	Ketika Cinta Bertasbih	Habiburrahman El Shirazy	Republika	2007-01-01	public/img/books/kcb.jpeg	t	\N
10	1	Dalam Mihrab Cinta	Habiburrahman El Shirazy	Republika	2009-01-01	public/img/books/dalam_mihrab_cinta.jpeg	t	\N
11	1	Negeri 5 Menara	Ahmad Fuadi	Gramedia	2009-01-01	public/img/books/negeri_5_menara.jpeg	t	\N
12	1	Ranah 3 Warna	Ahmad Fuadi	Gramedia	2011-01-01	public/img/books/ranah_3_warna.jpeg	t	\N
13	1	Rantau 1 Muara	Ahmad Fuadi	Gramedia	2013-01-01	public/img/books/rantau_1_muara.jpeg	t	\N
14	1	Bumi	Tere Liye	Gramedia	2014-01-01	public/img/books/bumi.jpeg	t	\N
15	1	Bulan	Tere Liye	Gramedia	2015-01-01	public/img/books/bulan.jpeg	t	\N
16	1	Matahari	Tere Liye	Gramedia	2016-01-01	public/img/books/matahari.jpeg	t	\N
17	1	Hujan	Tere Liye	Gramedia	2016-01-01	public/img/books/hujan.jpeg	t	\N
18	1	Pulang	Tere Liye	Republika	2015-01-01	public/img/books/pulang.jpeg	t	\N
19	1	Rindu	Tere Liye	Republika	2014-01-01	public/img/books/rindu.jpeg	t	\N
20	1	Pergi	Tere Liye	Gramedia	2018-01-01	public/img/books/pergi.jpeg	t	\N
21	3	Filosofi Teras	Henry Manampiring	Kompas	2018-01-01	public/img/books/filosofi_teras.jpeg	t	\N
22	3	Berani Tidak Disukai	Ichiro Kishimi	Gramedia	2019-01-01	public/img/books/berani_tidak_disukai.jpeg	t	\N
23	3	Atomic Habits	James Clear	Gramedia	2019-01-01	public/img/books/atomic_habits.jpeg	t	\N
24	3	The Psychology of Money	Morgan Housel	Gramedia	2021-01-01	public/img/books/psychology_of_money.jpeg	t	\N
25	3	Sebuah Seni Bersikap Bodo Amat	Mark Manson	Gramedia	2018-01-01	public/img/books/bodo_amat.jpeg	t	\N
26	2	Algoritma dan Pemrograman	Sukamto	Informatika	2017-01-01	public/img/books/algoritma.jpeg	t	\N
27	2	Pemrograman Java	Abdul Kadir	Andi	2018-01-01	public/img/books/java.jpeg	t	\N
28	2	Basis Data	Rosa A.S.	Informatika	2019-01-01	public/img/books/basis_data.jpeg	t	\N
29	2	Rekayasa Perangkat Lunak	Pressman	Andi	2015-01-01	public/img/books/rpl.jpeg	t	\N
30	2	Sistem Informasi	Gordon B. Davis	Salemba Empat	2014-01-01	public/img/books/sistem_informasi.jpeg	t	\N
31	4	Bumi Manusia	Pramoedya Ananta Toer	Hasta Mitra	1980-01-01	public/img/books/bumi_manusia.jpeg	t	\N
32	4	Anak Semua Bangsa	Pramoedya Ananta Toer	Hasta Mitra	1981-01-01	public/img/books/anak_semua_bangsa.jpeg	t	\N
33	4	Jejak Langkah	Pramoedya Ananta Toer	Hasta Mitra	1985-01-01	public/img/books/jejak_langkah.jpeg	t	\N
34	4	Rumah Kaca	Pramoedya Ananta Toer	Hasta Mitra	1988-01-01	public/img/books/rumah_kaca.jpeg	t	\N
35	4	Habibie & Ainun	B.J. Habibie	THC Mandiri	2010-01-01	public/img/books/habibie_ainun.jpeg	t	\N
36	5	Cerita Rakyat Nusantara	James Danandjaja	Balai Pustaka	2007-01-01	public/img/books/cerita_nusantara.jpeg	t	\N
37	5	Dongeng Anak Indonesia	Tim Bobo	Gramedia	2012-01-01	public/img/books/dongeng_anak.jpeg	t	\N
38	5	Si Kancil	Anonim	Balai Pustaka	1995-01-01	public/img/books/si_kancil.jpeg	t	\N
39	5	Malin Kundang	Anonim	Balai Pustaka	1995-01-01	public/img/books/malin_kundang.jpg	t	\N
40	5	Timun Mas	Anonim	Balai Pustaka	1996-01-01	public/img/books/timun_mas.jpg	t	\N
41	1	Dilan 1990	Pidi Baiq	Pastel Books	2014-01-01	public/img/books/dilan_1990.jpg	t	\N
42	1	Dilan 1991	Pidi Baiq	Pastel Books	2015-01-01	public/img/books/dilan_1991.jpg	t	\N
43	1	Milea	Pidi Baiq	Pastel Books	2016-01-01	public/img/books/milea.jpg	t	\N
44	1	Garis Waktu	Fiersa Besari	Media Kita	2016-01-01	public/img/books/garis_waktu.jpg	t	\N
45	1	Catatan Juang	Fiersa Besari	Media Kita	2015-01-01	public/img/books/catatan_juang.jpg	t	\N
46	3	You Do You	Fellexandro Ruby	Gramedia	2020-01-01	public/img/books/you_do_you.jpg	t	\N
47	3	Ikigai	Hector Garcia	Gramedia	2018-01-01	public/img/books/ikigai.jpg	t	\N
48	2	Cloud Computing	Onno W. Purbo	Andi	2017-01-01	public/img/books/cloud.jpeg	t	\N
49	2	Manajemen Proyek TI	Indrajit	Gramedia	2016-01-01	public/img/books/manajemen_proyek.jpeg	t	\N
50	2	Data Mining	Kusrini	Andi	2015-01-01	public/img/books/data_mining.jpeg	t	\N
51	1	Rasa	Tere Liye	Gramedia	2022-04-22	public/img/books/pergi.jpg	t	\N
52	1	Komet	Tere Liye	Gramedia	2018-01-01	public/img/books/komet.jpg	t	\N
53	1	Komet Minor	Tere Liye	Gramedia	2019-01-01	public/img/books/komet_minor.jpg	t	\N
54	1	Selena	Tere Liye	Gramedia	2020-01-01	public/img/books/selena.jpg	t	\N
55	1	Nebula	Tere Liye	Gramedia	2020-01-01	public/img/books/nebula.jpg	t	\N
56	1	Rantau	Tere Liye	Republika	2017-01-01	public/img/books/rantau.jpg	t	\N
57	1	Tentang Kamu	Tere Liye	Republika	2016-01-01	public/img/books/tentang_kamu.jpg	t	\N
58	1	Sunset Bersama Rosie	Tere Liye	Republika	2011-01-01	public/img/books/sunset_rosie.jpg	t	\N
59	1	Bidadari-Bidadari Surga	Tere Liye	Republika	2009-01-01	public/img/books/bidadari_surga.jpg	t	\N
60	1	Hafalan Shalat Delisa	Tere Liye	Republika	2005-01-01	public/img/books/delisa.jpg	t	\N
61	1	Pulang Pergi	Tere Liye	Gramedia	2021-01-01	public/img/books/pulang_pergi.jpg	t	\N
62	1	Janji	Tere Liye	Gramedia	2021-01-01	public/img/books/janji.jpg	t	\N
63	1	Garis Waktu	Fiersa Besari	Media Kita	2016-01-01	public/img/books/garis_waktu.jpg	t	\N
64	1	11:11	Fiersa Besari	Media Kita	2018-01-01	public/img/books/1111.jpg	t	\N
65	1	Konspirasi Alam Semesta	Fiersa Besari	Media Kita	2017-01-01	public/img/books/konspirasi_alam_semesta.jpg	t	\N
66	1	Arah Langkah	Fiersa Besari	Media Kita	2019-01-01	public/img/books/arah_langkah.jpg	t	\N
67	1	Tapak Jejak	Fiersa Besari	Media Kita	2015-01-01	public/img/books/tapak_jejak.jpg	t	\N
68	1	Catatan Juang	Fiersa Besari	Media Kita	2015-01-01	public/img/books/catatan_juang.jpg	t	\N
69	1	Pulang	Leila S. Chudori	Kepustakaan Populer Gramedia	2012-01-01	public/img/books/pulang_leila.jpg	t	\N
70	1	Laut Bercerita	Leila S. Chudori	Kepustakaan Populer Gramedia	2017-01-01	public/img/books/laut_bercerita.jpg	t	\N
71	1	Namaku Alam	Leila S. Chudori	Kepustakaan Populer Gramedia	2023-01-01	public/img/books/namaku_alam.jpg	t	\N
72	4	Amba	Laksmi Pamuntjak	Gramedia	2012-01-01	public/img/books/amba.jpg	t	\N
73	4	Aruna dan Lidahnya	Laksmi Pamuntjak	Gramedia	2014-01-01	public/img/books/aruna_lidahnya.jpg	t	\N
74	4	Cantik Itu Luka	Eka Kurniawan	Gramedia	2002-01-01	public/img/books/cantik_itu_luka.jpg	t	\N
75	4	Lelaki Harimau	Eka Kurniawan	Gramedia	2004-01-01	public/img/books/lelaki_harimau.jpg	t	\N
76	4	Seperti Dendam, Rindu Harus Dibayar Tuntas	Eka Kurniawan	Gramedia	2014-01-01	public/img/books/dendam.jpg	t	\N
77	4	O	Eka Kurniawan	Gramedia	2016-01-01	public/img/books/o.jpg	t	\N
78	4	Orang-Orang Proyek	Ahmad Tohari	Gramedia	2007-01-01	public/img/books/orang_proyek.jpg	t	\N
79	4	Ronggeng Dukuh Paruk	Ahmad Tohari	Gramedia	1982-01-01	public/img/books/ronggeng.jpg	t	\N
80	4	Lintang Kemukus Dini Hari	Ahmad Tohari	Gramedia	1985-01-01	public/img/books/lintang_kemukus.jpg	t	\N
81	5	Kumpulan Cerita Rakyat Jawa	Tim Balai Pustaka	Balai Pustaka	2005-01-01	public/img/books/cerita_jawa.jpg	t	\N
82	5	Kumpulan Cerita Rakyat Sumatra	Tim Balai Pustaka	Balai Pustaka	2006-01-01	public/img/books/cerita_sumatra.jpg	t	\N
83	5	Kumpulan Cerita Rakyat Kalimantan	Tim Balai Pustaka	Balai Pustaka	2007-01-01	public/img/books/cerita_kalimantan.jpg	t	\N
84	5	Kumpulan Cerita Rakyat Sulawesi	Tim Balai Pustaka	Balai Pustaka	2008-01-01	public/img/books/cerita_sulawesi.jpg	t	\N
85	5	Kumpulan Cerita Rakyat Papua	Tim Balai Pustaka	Balai Pustaka	2009-01-01	public/img/books/cerita_papua.jpg	t	\N
86	2	Pengantar Teknologi Informasi	Jogiyanto	Andi	2016-01-01	public/img/books/pti.jpg	t	\N
87	2	Analisis dan Desain Sistem Informasi	Jogiyanto	Andi	2017-01-01	public/img/books/adsi.jpg	t	\N
88	2	Data Warehouse	Inmon	Andi	2015-01-01	public/img/books/data_warehouse.jpg	t	\N
89	2	Big Data Analytics	Eko Prasetyo	Informatika	2018-01-01	public/img/books/big_data.jpg	t	\N
90	2	Artificial Intelligence	Suyanto	Informatika	2020-01-01	public/img/books/ai.jpg	t	\N
91	3	Goodbye Things	Fumio Sasaki	Gramedia	2019-01-01	public/img/books/goodbye_things.jpg	t	\N
92	3	Mindset	Carol S. Dweck	Gramedia	2017-01-01	public/img/books/mindset.jpg	t	\N
93	3	Deep Work	Cal Newport	Gramedia	2018-01-01	public/img/books/deep_work.jpg	t	\N
94	3	Start With Why	Simon Sinek	Gramedia	2016-01-01	public/img/books/start_with_why.jpg	t	\N
95	3	Grit	Angela Duckworth	Gramedia	2017-01-01	public/img/books/grit.jpg	t	\N
96	5	Ensiklopedia Anak Pintar	Tim Edukasi	Gramedia	2015-01-01	public/img/books/ensiklopedia_anak.jpg	t	\N
97	5	Sains untuk Anak	Tim Edukasi	Gramedia	2016-01-01	public/img/books/sains_anak.jpg	t	\N
98	5	Matematika Dasar Anak	Tim Edukasi	Gramedia	2017-01-01	public/img/books/matematika_anak.jpg	t	\N
99	5	Bahasa Indonesia Anak	Tim Edukasi	Gramedia	2018-01-01	public/img/books/bahasa_anak.jpg	t	\N
100	5	Cerita Bergambar Nusantara	Tim Edukasi	Gramedia	2019-01-01	public/img/books/cerita_bergambar.jpg	t	\N
1	1	Laskar Pelangi	Andrea Hirata	Bentang Pustaka	2005-01-01	public/img/books/laskar_pelangi.jpeg	t	Ini adalah sinopsis buku yang sangat seru...
\.


--
-- Data for Name: bookcategory; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bookcategory (category_id, category_name, explanation) FROM stdin;
1	Novel Fiksi Populer Indonesia	Buku fiksi berbentuk novel dengan tema kehidupan, percintaan, religiusitas, dan petualangan untuk pembaca umum
2	Buku Teknologi dan Sistem Informasi	Buku nonfiksi yang membahas teori dan penerapan teknologi informasi, pemrograman, dan sistem informasi
3	Pengembangan Diri dan Psikologi Populer	Buku nonfiksi tentang pengembangan kepribadian, motivasi, dan pola pikir praktis
4	Sastra Indonesia	Karya sastra fiksi bernilai budaya, sosial, dan historis yang ditulis oleh sastrawan Indonesia
5	Buku Anak dan Cerita Rakyat	Buku bacaan anak yang berisi dongeng, cerita rakyat, dan materi edukasi dasar
\.


--
-- Data for Name: booklending; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.booklending (loan_id, book_id, loan_date, due_date, return_date, id) FROM stdin;
\.


--
-- Data for Name: bookreturn; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bookreturn (return_id, loan_id, return_date, penalty_id) FROM stdin;
\.


--
-- Data for Name: penalty; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.penalty (penalty_id, id, large_fines) FROM stdin;
\.


--
-- Data for Name: username; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.username (id, username, password, status, name, email, phone_number, google_id, auth_provider) FROM stdin;
1	admin	$2y$10$j26jMpvRnl5UjWbgfpoZnenGaVGBBWWgxgWTWuYRN2O0nBaj18CEe	admin	Administrator	admin@library.com	0811111111	\N	local
11	maul	$2y$10$t4PiQ34uw/SwwJBRDvfn3uYFzsvaNsxHlO/CRdqS7UupxT3QUyt4K	member	Imam Maulana	imammaul@gmail.com	0856987844	\N	local
12	ipemalisjakartaoffic	\N	member	IPEMALIS Jakarta	ipemalisjakarta.official@gmail.com	\N	109044630801560508018	google
13	agilrazzan	\N	member	2024 | Agil Razzan Murtadha	agil.razzan@students.uag.ac.id	\N	107523761430669448320	google
2	andiwijaya	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Andi Wijaya	andi@mail.com	0812222222	\N	local
3	budisantoso	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Budi Santoso	budi@mail.com	0813333333	\N	local
4	citralestari	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Citra Lestari	citra@mail.com	0814444444	\N	local
5	dewianggraini	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Dewi Anggraini	dewi@mail.com	0815555555	\N	local
6	ekoprasetyo	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Eko Prasetyo	eko@mail.com	0816666666	\N	local
7	fajarhidayat	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Fajar Hidayat	fajar@mail.com	0817777777	\N	local
8	gitapermata	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Gita Permata	gita@mail.com	0818888888	\N	local
9	hadikurniawan	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Hadi Kurniawan	hadi@mail.com	0819999999	\N	local
10	indahsari	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Indah Sari	indah@mail.com	0820000000	\N	local
\.


--
-- Data for Name: waiting_list; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.waiting_list (waiting_id, book_id, id, request_date) FROM stdin;
\.


--
-- Name: penalty_penalty_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.penalty_penalty_id_seq', 1, false);


--
-- Name: waiting_list_waiting_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.waiting_list_waiting_id_seq', 1, false);


--
-- Name: book book_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.book
    ADD CONSTRAINT book_pkey PRIMARY KEY (book_id);


--
-- Name: bookcategory bookcategory_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookcategory
    ADD CONSTRAINT bookcategory_pkey PRIMARY KEY (category_id);


--
-- Name: booklending booklending_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booklending
    ADD CONSTRAINT booklending_pkey PRIMARY KEY (loan_id);


--
-- Name: bookreturn bookreturn_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookreturn
    ADD CONSTRAINT bookreturn_pkey PRIMARY KEY (return_id);


--
-- Name: penalty penalty_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penalty
    ADD CONSTRAINT penalty_pkey PRIMARY KEY (penalty_id);


--
-- Name: username username_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.username
    ADD CONSTRAINT username_email_key UNIQUE (email);


--
-- Name: username username_google_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.username
    ADD CONSTRAINT username_google_id_key UNIQUE (google_id);


--
-- Name: username username_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.username
    ADD CONSTRAINT username_pkey PRIMARY KEY (id);


--
-- Name: username username_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.username
    ADD CONSTRAINT username_username_key UNIQUE (username);


--
-- Name: waiting_list waiting_list_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.waiting_list
    ADD CONSTRAINT waiting_list_pkey PRIMARY KEY (waiting_id);


--
-- Name: idx_auth_provider; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_auth_provider ON public.username USING btree (auth_provider);


--
-- Name: idx_book_title; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_book_title ON public.book USING btree (book_title);


--
-- Name: idx_google_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_google_id ON public.username USING btree (google_id);


--
-- Name: ux_one_active_loan; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ux_one_active_loan ON public.booklending USING btree (book_id) WHERE (return_date IS NULL);


--
-- Name: booklending trigger_book_status; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_book_status AFTER INSERT OR UPDATE ON public.booklending FOR EACH ROW EXECUTE FUNCTION public.update_book_status();


--
-- Name: booklending booklending_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booklending
    ADD CONSTRAINT booklending_id_fkey FOREIGN KEY (id) REFERENCES public.username(id);


--
-- Name: book fk_bookcategory; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.book
    ADD CONSTRAINT fk_bookcategory FOREIGN KEY (category_id) REFERENCES public.bookcategory(category_id);


--
-- Name: booklending fk_lendingbook; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booklending
    ADD CONSTRAINT fk_lendingbook FOREIGN KEY (book_id) REFERENCES public.book(book_id);


--
-- Name: penalty fk_penaltyuser; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penalty
    ADD CONSTRAINT fk_penaltyuser FOREIGN KEY (id) REFERENCES public.username(id);


--
-- Name: bookreturn fk_returnlending; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookreturn
    ADD CONSTRAINT fk_returnlending FOREIGN KEY (loan_id) REFERENCES public.booklending(loan_id);


--
-- Name: bookreturn fk_returnpenalty; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bookreturn
    ADD CONSTRAINT fk_returnpenalty FOREIGN KEY (penalty_id) REFERENCES public.penalty(penalty_id);


--
-- Name: waiting_list fk_waiting_book; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.waiting_list
    ADD CONSTRAINT fk_waiting_book FOREIGN KEY (book_id) REFERENCES public.book(book_id);


--
-- Name: waiting_list fk_waiting_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.waiting_list
    ADD CONSTRAINT fk_waiting_user FOREIGN KEY (id) REFERENCES public.username(id);


--
-- Name: booklending; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.booklending ENABLE ROW LEVEL SECURITY;

--
-- Name: bookreturn; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.bookreturn ENABLE ROW LEVEL SECURITY;

--
-- Name: booklending mahasiswa_check_peminjaman_sendiri; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY mahasiswa_check_peminjaman_sendiri ON public.booklending FOR SELECT TO mahasiswa USING ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.username)::text = CURRENT_USER))));


--
-- Name: waiting_list mahasiswa_check_waiting_list_sendiri; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY mahasiswa_check_waiting_list_sendiri ON public.waiting_list FOR SELECT TO mahasiswa USING ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.username)::text = CURRENT_USER))));


--
-- Name: booklending policy_peminjaman_pribadi; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY policy_peminjaman_pribadi ON public.booklending FOR SELECT TO mahasiswa USING ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.username)::text = CURRENT_USER))));


--
-- Name: bookreturn policy_pengembalian_pribadi; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY policy_pengembalian_pribadi ON public.bookreturn FOR SELECT TO mahasiswa USING ((loan_id IN ( SELECT booklending.loan_id
   FROM public.booklending
  WHERE (booklending.id = ( SELECT username.id
           FROM public.username
          WHERE ((username.username)::text = CURRENT_USER))))));


--
-- Name: waiting_list policy_waiting_pribadi; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY policy_waiting_pribadi ON public.waiting_list FOR SELECT TO mahasiswa USING ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.name)::text = CURRENT_USER))));


--
-- Name: username student_view_own_data; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY student_view_own_data ON public.username FOR SELECT TO mahasiswa USING (((username)::text = CURRENT_USER));


--
-- Name: username; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.username ENABLE ROW LEVEL SECURITY;

--
-- Name: waiting_list; Type: ROW SECURITY; Schema: public; Owner: postgres
--

ALTER TABLE public.waiting_list ENABLE ROW LEVEL SECURITY;

--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT USAGE ON SCHEMA public TO staff_perpus;


--
-- Name: TABLE book; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.book TO staff_perpus;


--
-- Name: TABLE bookcategory; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.bookcategory TO staff_perpus;


--
-- Name: TABLE booklending; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.booklending TO staff_perpus;


--
-- Name: TABLE bookreturn; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.bookreturn TO staff_perpus;


--
-- Name: TABLE penalty; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.penalty TO staff_perpus;


--
-- Name: TABLE username; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.username TO staff_perpus;


--
-- Name: TABLE waiting_list; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.waiting_list TO staff_perpus;


--
-- Name: mv_rekap_denda; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: postgres
--

REFRESH MATERIALIZED VIEW public.mv_rekap_denda;


--
-- Name: mv_rekap_denda_member; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: postgres
--

REFRESH MATERIALIZED VIEW public.mv_rekap_denda_member;


--
-- Name: mv_statistik_member; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: postgres
--

REFRESH MATERIALIZED VIEW public.mv_statistik_member;


--
-- Name: mv_total_denda; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: postgres
--

REFRESH MATERIALIZED VIEW public.mv_total_denda;


--
-- PostgreSQL database dump complete
--

\unrestrict Xh85XZA1I60XEycx8xbPUIXPKEsmnZSOfE0BVGphM9fJaJLUCympuVwwGdoA2ra

