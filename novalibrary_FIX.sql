--
-- PostgreSQL database dump
--

\restrict JjOQosjescbzLEVULMUqXdKxRQclh7mVlHHCDzyDAho5f1fofD5ZutwjoCGB6ks

-- Dumped from database version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)

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
    v_penalty_amount INT;
BEGIN
    -- Get due date and user id
    SELECT due_date, id INTO v_due_date, v_user_id 
    FROM booklending WHERE loan_id = p_loan_id;

    v_late_days := p_return_date - v_due_date;

    -- If late, insert penalty (Rp 2.000 per day)
    IF v_late_days > 0 THEN
        v_penalty_amount := v_late_days * 2000; -- Changed from 5000 to 2000
        INSERT INTO penalty (id, large_fines) 
        VALUES (v_user_id, v_penalty_amount);
        RAISE NOTICE 'Denda Rp % untuk % hari keterlambatan', v_penalty_amount, v_late_days;
    END IF;

    -- Insert return record
    INSERT INTO bookreturn (loan_id, return_date) 
    VALUES (p_loan_id, p_return_date);
    
    -- Update book status to available
    UPDATE book SET book_status = true 
    WHERE book_id = (SELECT book_id FROM booklending WHERE loan_id = p_loan_id);
END;
$$;


ALTER PROCEDURE public.proses_kembali_dan_denda(IN p_loan_id integer, IN p_return_date date) OWNER TO postgres;

--
-- Name: update_book_status(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_book_status() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- When a book is borrowed (INSERT), set status to false (unavailable)
    IF TG_OP = 'INSERT' THEN
        UPDATE book SET book_status = false
        WHERE book_id = NEW.book_id;  -- FIXED: was nook_id
    END IF;

    -- When a book is returned (UPDATE with return_date), set status to true (available)
    IF TG_OP = 'UPDATE' AND NEW.return_date IS NOT NULL THEN
        UPDATE book SET book_status = true
        WHERE book_id = NEW.book_id;
    END IF;

    RETURN NEW;
END;
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
    large_fines integer NOT NULL,
    paid boolean DEFAULT false,
    paid_date date
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
22	3	Berani Tidak Disukai	Ichiro Kishimi	Gramedia	2019-01-01	public/img/books/berani_tidak_disukai.jpeg	t	\N
27	2	Pemrograman Java	Abdul Kadir	Andi	2018-01-01	public/img/books/java.jpeg	t	\N
29	2	Rekayasa Perangkat Lunak	Pressman	Andi	2015-01-01	public/img/books/rpl.jpeg	t	\N
30	2	Sistem Informasi	Gordon B. Davis	Salemba Empat	2014-01-01	public/img/books/sistem_informasi.jpeg	t	\N
34	4	Rumah Kaca	Pramoedya Ananta Toer	Hasta Mitra	1988-01-01	public/img/books/rumah_kaca.jpeg	t	\N
36	5	Cerita Rakyat Nusantara	James Danandjaja	Balai Pustaka	2007-01-01	public/img/books/cerita_nusantara.jpeg	t	\N
39	5	Malin Kundang	Anonim	Balai Pustaka	1995-01-01	public/img/books/malin_kundang.jpg	t	\N
40	5	Timun Mas	Anonim	Balai Pustaka	1996-01-01	public/img/books/timun_mas.jpg	t	\N
43	1	Milea	Pidi Baiq	Pastel Books	2016-01-01	public/img/books/milea.jpg	t	\N
45	1	Catatan Juang	Fiersa Besari	Media Kita	2015-01-01	public/img/books/catatan_juang.jpg	t	\N
46	3	You Do You	Fellexandro Ruby	Gramedia	2020-01-01	public/img/books/you_do_you.jpg	t	\N
47	3	Ikigai	Hector Garcia	Gramedia	2018-01-01	public/img/books/ikigai.jpg	t	\N
49	2	Manajemen Proyek TI	Indrajit	Gramedia	2016-01-01	public/img/books/manajemen_proyek.jpeg	t	\N
50	2	Data Mining	Kusrini	Andi	2015-01-01	public/img/books/data_mining.jpeg	t	\N
51	1	Rasa	Tere Liye	Gramedia	2022-04-22	public/img/books/pergi.jpg	t	\N
53	1	Komet Minor	Tere Liye	Gramedia	2019-01-01	public/img/books/komet_minor.jpg	t	\N
54	1	Selena	Tere Liye	Gramedia	2020-01-01	public/img/books/selena.jpg	t	\N
56	1	Rantau	Tere Liye	Republika	2017-01-01	public/img/books/rantau.jpg	t	\N
57	1	Tentang Kamu	Tere Liye	Republika	2016-01-01	public/img/books/tentang_kamu.jpg	t	\N
58	1	Sunset Bersama Rosie	Tere Liye	Republika	2011-01-01	public/img/books/sunset_rosie.jpg	t	\N
60	1	Hafalan Shalat Delisa	Tere Liye	Republika	2005-01-01	public/img/books/delisa.jpg	t	\N
61	1	Pulang Pergi	Tere Liye	Gramedia	2021-01-01	public/img/books/pulang_pergi.jpg	t	\N
64	1	11:11	Fiersa Besari	Media Kita	2018-01-01	public/img/books/1111.jpg	t	\N
65	1	Konspirasi Alam Semesta	Fiersa Besari	Media Kita	2017-01-01	public/img/books/konspirasi_alam_semesta.jpg	t	\N
7	1	Rectoverso	Dee Lestari	Bentang Pustaka	2008-01-01	public/img/books/rectoverso.jpeg	t	Rectoverso karya Dee Lestari adalah buku unik yang menggabungkan 11 cerita pendek (cerpen) dan 11 lagu yang saling melengkapi, menawarkan pengalaman membaca dan mendengarkan dalam tema besar cinta yang tak terucapkan, melankolis, dan penuh makna, dengan cerita-cerita yang menyentuh hati tentang rasa, kehilangan, dan ketakutan dalam hubungan, termasuk beberapa lagu legendaris seperti "Malaikat Juga Tahu" dan "Firasat". Setiap cerita memiliki "kembaran" lagu yang memberikan dimensi berbeda, bisa dinikmati terpisah atau bersamaan, menciptakan karya hibrida yang inovatif dan membekas di benak pembaca
38	5	Si Kancil	Anonim	Balai Pustaka	1995-01-01	public/img/books/book_1768215826_6964d51207941.jpeg	t	
62	1	Janji	Tere Liye	Gramedia	2021-01-01	public/img/books/book_1768215697_6964d4915f2af.jpg	t	
44	1	Garis Waktu	Fiersa Besari	Media Kita	2016-01-01	public/img/books/garis_waktu.jpg	f	\N
26	2	Algoritma dan Pemrograman	Sukamto	Informatika	2017-01-01	public/img/books/book_1768215258_6964d2da7827f.jpeg	t	
9	1	Ketika Cinta Bertasbih	Habiburrahman El Shirazy	Republika	2007-01-01	public/img/books/kcb.jpeg	f	Ketika Cinta Bertasbih karya Habiburrahman El Shirazy adalah novel religi fenomenal yang mengisahkan perjuangan hidup dan cinta Islami, berpusat pada tokoh Azzam, mahasiswa Al-Azhar, Kairo, yang bekerja keras menjual tempe demi menafkahi keluarga di Indonesia sambil mengejar impian dan cinta sejatinya, Anna Althafunnisa, penuh liku, cobaan, kesabaran, dan keteguhan prinsip agama hingga akhirnya menemukan kebahagiaan sejati.
52	1	Komet	Tere Liye	Gramedia	2018-01-01	public/img/books/komet.jpg	f	\N
63	1	Garis Waktu	Fiersa Besari	Media Kita	2016-01-01	public/img/books/garis_waktu.jpg	f	\N
35	4	Habibie & Ainun	B.J. Habibie	THC Mandiri	2010-01-01	public/img/books/book_1768215918_6964d56e3e140.jpeg	t	
48	2	Cloud Computing	Onno W. Purbo	Andi	2017-01-01	public/img/books/cloud.jpeg	f	\N
59	1	Bidadari-Bidadari Surga	Tere Liye	Republika	2009-01-01	public/img/books/bidadari_surga.jpg	f	\N
66	1	Arah Langkah	Fiersa Besari	Media Kita	2019-01-01	public/img/books/arah_langkah.jpg	f	\N
11	1	Negeri 5 Menara	Ahmad Fuadi	Gramedia	2009-01-01	public/img/books/book_1768215943_6964d5870c15f.jpeg	t	Negeri 5 Menara adalah novel inspiratif karya Ahmad Fuadi yang mengisahkan perjuangan Alif Fikri dan lima sahabatnya di Pondok Madani, Ponorogo, Jawa Timur, mengejar mimpi mereka di bawah slogan "Man Jadda Wajada" (Siapa yang bersungguh-sungguh akan berhasil). Novel ini menggambarkan kehidupan pesantren yang penuh persahabatan, pendidikan, tantangan, dan semangat meraih cita-cita tinggi, dengan latar belakang Alif yang awalnya enggan di pesantren namun akhirnya menemukan kekuatan dari teman-temannya untuk memandang awan dan membayangkan impian seperti kuliah di luar negeri.
37	5	Dongeng Anak Indonesia	Tim Bobo	Gramedia	2012-01-01	public/img/books/book_1768215901_6964d55d327cf.jpeg	f	
2	1	Sang Pemimpi	Andrea Hirata	Bentang Pustaka	2006-01-01	public/img/books/book_1767352230_6957a7a643af3.jpg	t	\N
32	4	Anak Semua Bangsa	Pramoedya Ananta Toer	Hasta Mitra	1981-01-01	public/img/books/book_1768215275_6964d2ebccab7.jpeg	t	
20	1	Pergi	Tere Liye	Gramedia	2018-01-01	public/img/books/book_1768215350_6964d336abdec.jpeg	t	
24	3	The Psychology of Money	Morgan Housel	Gramedia	2021-01-01	public/img/books/psychology_of_money.jpeg	f	\N
28	2	Basis Data	Rosa A.S.	Informatika	2019-01-01	public/img/books/book_1768215367_6964d34771612.jpeg	f	
42	1	Dilan 1991	Pidi Baiq	Pastel Books	2015-01-01	public/img/books/dilan_1991.jpg	f	\N
68	1	Catatan Juang	Fiersa Besari	Media Kita	2015-01-01	public/img/books/catatan_juang.jpg	t	\N
70	1	Laut Bercerita	Leila S. Chudori	Kepustakaan Populer Gramedia	2017-01-01	public/img/books/laut_bercerita.jpg	t	\N
71	1	Namaku Alam	Leila S. Chudori	Kepustakaan Populer Gramedia	2023-01-01	public/img/books/namaku_alam.jpg	t	\N
74	4	Cantik Itu Luka	Eka Kurniawan	Gramedia	2002-01-01	public/img/books/cantik_itu_luka.jpg	t	\N
75	4	Lelaki Harimau	Eka Kurniawan	Gramedia	2004-01-01	public/img/books/lelaki_harimau.jpg	t	\N
76	4	Seperti Dendam, Rindu Harus Dibayar Tuntas	Eka Kurniawan	Gramedia	2014-01-01	public/img/books/dendam.jpg	t	\N
78	4	Orang-Orang Proyek	Ahmad Tohari	Gramedia	2007-01-01	public/img/books/orang_proyek.jpg	t	\N
79	4	Ronggeng Dukuh Paruk	Ahmad Tohari	Gramedia	1982-01-01	public/img/books/ronggeng.jpg	t	\N
81	5	Kumpulan Cerita Rakyat Jawa	Tim Balai Pustaka	Balai Pustaka	2005-01-01	public/img/books/cerita_jawa.jpg	t	\N
82	5	Kumpulan Cerita Rakyat Sumatra	Tim Balai Pustaka	Balai Pustaka	2006-01-01	public/img/books/cerita_sumatra.jpg	t	\N
83	5	Kumpulan Cerita Rakyat Kalimantan	Tim Balai Pustaka	Balai Pustaka	2007-01-01	public/img/books/cerita_kalimantan.jpg	t	\N
85	5	Kumpulan Cerita Rakyat Papua	Tim Balai Pustaka	Balai Pustaka	2009-01-01	public/img/books/cerita_papua.jpg	t	\N
86	2	Pengantar Teknologi Informasi	Jogiyanto	Andi	2016-01-01	public/img/books/pti.jpg	t	\N
87	2	Analisis dan Desain Sistem Informasi	Jogiyanto	Andi	2017-01-01	public/img/books/adsi.jpg	t	\N
89	2	Big Data Analytics	Eko Prasetyo	Informatika	2018-01-01	public/img/books/big_data.jpg	t	\N
90	2	Artificial Intelligence	Suyanto	Informatika	2020-01-01	public/img/books/ai.jpg	t	\N
92	3	Mindset	Carol S. Dweck	Gramedia	2017-01-01	public/img/books/mindset.jpg	t	\N
93	3	Deep Work	Cal Newport	Gramedia	2018-01-01	public/img/books/deep_work.jpg	t	\N
96	5	Ensiklopedia Anak Pintar	Tim Edukasi	Gramedia	2015-01-01	public/img/books/ensiklopedia_anak.jpg	t	\N
97	5	Sains untuk Anak	Tim Edukasi	Gramedia	2016-01-01	public/img/books/sains_anak.jpg	t	\N
98	5	Matematika Dasar Anak	Tim Edukasi	Gramedia	2017-01-01	public/img/books/matematika_anak.jpg	t	\N
99	5	Bahasa Indonesia Anak	Tim Edukasi	Gramedia	2018-01-01	public/img/books/bahasa_anak.jpg	t	\N
100	5	Cerita Bergambar Nusantara	Tim Edukasi	Gramedia	2019-01-01	public/img/books/cerita_bergambar.jpg	t	\N
3	1	Edensor	Andrea Hirata	Bentang Pustaka	2007-01-01	public/img/books/book_1768128391_69637f87935d9.jpg	f	Aku ingin mendaki puncak tantangan, menerjang batu granit kesulitan, menggoda mara bahaya, dan memecahkan misteri dengan sains. Aku ingin menghirup berupa-rupa pengalaman lalu terjun bebas menyelami labirin lika-liku hidup yang ujungnya tak dapat disangka. Aku mendamba kehidupan dengan kemungkinan-kemungkinan yang bereaksi satu sama lain seperti benturan molekul uranium: meletup tak terduga-duga, menyerap, mengikat, mengganda, berkembang, terurai, dan berpencar ke arah yang mengejutkan. Aku ingin ke tempat-tempat yang jauh, menjumpai beragam bahasa dan orang-orang asing. Aku ingin berkelana, menemukan arahku dengan membaca bintang gemintang. Aku ingin mengarungi padang dan gurun-gurun, ingin melepuh terbakar matahari, limbung dihantam angin, dan menciut dicengkeram dingin. Aku ingin kehidupan yang menggetarkan, penuh dengan penaklukan. Aku ingin hidup! Ingin merasakan saripati hidup!
77	4	O	Eka Kurniawan	Gramedia	2016-01-01	public/img/books/o.jpg	f	\N
84	5	Kumpulan Cerita Rakyat Sulawesi	Tim Balai Pustaka	Balai Pustaka	2008-01-01	public/img/books/cerita_sulawesi.jpg	f	\N
91	3	Goodbye Things	Fumio Sasaki	Gramedia	2019-01-01	public/img/books/goodbye_things.jpg	f	\N
73	4	Aruna dan Lidahnya	Laksmi Pamuntjak	Gramedia	2014-01-01	public/img/books/aruna_lidahnya.jpg	f	\N
80	4	Lintang Kemukus Dini Hari	Ahmad Tohari	Gramedia	1985-01-01	public/img/books/lintang_kemukus.jpg	f	\N
88	2	Data Warehouse	Inmon	Andi	2015-01-01	public/img/books/data_warehouse.jpg	f	\N
95	3	Grit	Angela Duckworth	Gramedia	2017-01-01	public/img/books/grit.jpg	f	\N
55	1	Nebula	Tere Liye	Gramedia	2020-01-01	public/img/books/book_1768215774_6964d4ded5318.jpg	f	
25	3	Sebuah Seni Bersikap Bodo Amat	Mark Manson	Gramedia	2018-01-01	public/img/books/bodo_amat.jpeg	f	\N
33	4	Jejak Langkah	Pramoedya Ananta Toer	Hasta Mitra	1985-01-01	public/img/books/jejak_langkah.jpeg	f	\N
41	1	Dilan 1990	Pidi Baiq	Pastel Books	2014-01-01	public/img/books/dilan_1990.jpg	f	\N
67	1	Tapak Jejak	Fiersa Besari	Media Kita	2015-01-01	public/img/books/tapak_jejak.jpg	f	\N
72	4	Amba	Laksmi Pamuntjak	Gramedia	2012-01-01	public/img/books/amba.jpg	f	\N
5	1	Perahu Kertas	Dee Lestari	Bentang Pustaka	2009-01-01	public/img/books/perahu_kertas.jpeg	f	Perahu Kertas karya Dewi Lestari mengisahkan cinta rumit antara Kugy, gadis ceria yang ingin jadi penulis dongeng, dan Keenan, pelukis berbakat yang terpaksa kuliah ekonomi, yang bertemu di Bandung, diwarnai persahabatan, kesalahpahaman, orang ketiga (Wanda/Remi), hingga pendewasaan diri dan perjuangan mengejar mimpi masing-masing hingga akhirnya takdir kembali mempertemukan mereka, mengajarkan arti cinta sejati dan menghargai passion
69	1	Pulang	Leila S. Chudori	Kepustakaan Populer Gramedia	2012-01-01	public/img/books/pulang_leila.jpg	f	\N
94	3	Start With Why	Simon Sinek	Gramedia	2016-01-01	public/img/books/book_1768102189_6963192db0ceb.jpg	f	
1	1	Laskar Pelangi	Andrea Hirata	Bentang Pustaka	2005-01-01	public/img/books/book_1767352218_6957a79ae4696.jpg	f	Ini adalah sinopsis buku yang sangat seru...
101	5	Animal Farm	George Orwell	\N	1945-01-01	public/img/books/book_1768128332_69637f4c7a23d.jpg	t	Animal Farm (harfiah: "Perternakan Hewan", judul terjemahan: Binatangisme,[1] Republik Hewan) adalah sebuah novel pendek yang ditulis oleh George Orwell mengenai sekelompok hewan yang menggulingkan kekuasaan manusia di sebuah peternakan yang mereka miliki. Para petani manusia ini menindas para hewan. Lalu mereka sendiri mengendalikan peternakan, hanya untuk melihat keadaannya menjadi merosot dan mereka sendiri bertindak semena-mena. Buku ini ditulis semasa Perang Dunia II dan diterbitkan pada tahun tanggal 17 Agustus 1945, walaupun baru sukses pada penghujung dasawarsa 1950-an.
23	3	Atomic Habits	James Clear	Gramedia	2019-01-01	public/img/books/book_1768208953_6964ba3998ff7.jpg	t	Atomic Habits: An Easy & Proven Way to Build Good Habits & Break Bad Ones is a 2018 self-help book on habit formation by writer James Clear. The book received acclaim from most critics, with a few strongly disapproving of its claims. It became highly popular among readers in the years following its publication; as of February 2024, it has sold nearly 20 million copies, and had topped the New York Times best-seller list for 260 weeks (nearly 5 years).
31	4	Bumi Manusia	Pramoedya Ananta Toer	Hasta Mitra	1980-01-01	public/img/books/book_1768210072_6964be98100c5.jpg	f	Bumi Manusia (bahasa Inggris: This Earth of Mankind[1]) adalah buku pertama dari Tetralogi Buru karya Pramoedya Ananta Toer yang pertama kali diterbitkan oleh Hasta Mitra pada tahun 1980.\r\n\r\nBuku ini ditulis Pramoedya Ananta Toer ketika masih mendekam di Pulau Buru. Sebelum ditulis pada tahun 1975, sejak tahun 1973 terlebih dahulu telah diceritakan ulang kepada teman-temannya.\r\n\r\nSetelah diterbitkan, Bumi Manusia kemudian dilarang beredar setahun kemudian atas perintah Jaksa Agung.[2] Sebelum dilarang, buku ini sukses dengan 10 kali cetak ulang dalam setahun pada 1980-1981.[3] Sampai tahun 2005, buku ini telah diterbitkan dalam 33 bahasa. Pada September 2005, buku ini diterbitkan kembali di Indonesia oleh Lentera Dipantara.
6	1	Supernova 1	Dee Lestari	Truedee Pustaka	2001-01-01	public/img/books/book_1768140642_6963af62de0bf.jpg	t	Pada tahun 1991, Reuben, seorang Indo-Yahudi dan mahasiswa Johns Hopkins School of Medicine, bertemu dengan Dimas, seorang mahasiswa George Washington University yang berasal dari keluarga Indonesia yang berada, di Washington, D.C.. Mereka berdua menjadi semakin dekat, terlebih setelah Reuben mencoba alkohol pertamanya dan mulai melakukan refleksi tentang sifat dan watak alam semesta. Sepuluh tahun kemudian, Reuben dan Dimas telah menjadi pasangan gay dan menetap di sebelah selatan Jakarta. Melalui pengetahuan Reuben tentang watak alam semesta dan keahlian Dimas sebagai seorang penulis dan pujangga, mereka mulai menulis sebuah cerita cinta antara Kesatria, Putri, dan Bintang Jatuh.
10	1	Dalam Mihrab Cinta	Habiburrahman El Shirazy	Republika	2009-01-01	public/img/books/dalam_mihrab_cinta.jpeg	t	Dalam Mihrab Cinta adalah novelet (novel pendek) karya Habiburrahman El Shirazy yang berisi tiga cerita membangun jiwa tentang cinta yang berbalut nilai-nilai keislaman, mengangkat kisah utama tentang Syamsul, seorang santri yang difitnah dan menjadi pencopet, namun bangkit menjadi dai terkenal, mengajarkan keteguhan iman, kesabaran, serta keindahan cinta karena Allah. Novel ini kaya hikmah, menggunakan bahasa yang mudah dipahami dengan sentuhan diksi Arab dan Jawa, dan telah diadaptasi menjadi film layar lebar.
14	1	Bumi	Tere Liye	Gramedia	2014-01-01	public/img/books/bumi.jpeg	f	Bumi adalah novel fantasi karya Tere Liye yang mengisahkan petualangan Raib, seorang gadis 15 tahun yang bisa menghilang, bersama sahabatnya Seli (bisa petir) dan Ali (jenius), saat mereka tanpa sengaja tersesat ke dunia paralel, yaitu Klan Bulan, dan mengungkap misteri kekuatan mereka serta melawan musuh jahat bernama Tamus, mengajarkan persahabatan, keberanian, dan pentingnya kerja sama antar dunia.
16	1	Matahari	Tere Liye	Gramedia	2016-01-01	public/img/books/book_1768208833_6964b9c1b8d52.jpg	t	Namanya Ali, 15 tahun, kelas X. Jika saja orangtuanya mengizinkan, seharusnya dia sudah duduk di tingkat akhir ilmu fisika program doktor di universitas ternama. Ali tidak menyukai sekolahnya, guru-gurunya, teman-teman sekelasnya. Semua membosankan baginya. Tapi sejak dia mengetahui ada yang aneh pada diriku dan Seli, teman sekelasnya, hidupnya yang membosankan berubah seru. Aku bisa menghilang, dan Seli bisa mengeluarkan petir. Ali sendiri punya rahasia kecil. Dia bisa berubah menjadi beruang raksasa. Kami bertiga kemudian bertualang ke tempat-tempat menakjubkan. Namanya Ali. Dia tahu sejak dulu dunia ini tidak sesederhana yang dilihat orang. Dan di atas segalanya, dia akhirnya tahu persahabatan adalah hal yang paling utama.
4	1	Maryamah Karpov	Andrea Hirata	Bentang Pustaka	2008-01-01	public/img/books/book_1768215329_6964d321ac9e4.jpeg	t	Maryamah Karpov adalah novel keempat karya Andrea Hirata yang diterbitkan oleh Bentang Pustaka pada November 2008. Maryamah Karpov merupakan buku terakhir dari Tetralogi Laskar Pelangi dan terdiri dari 2 buku, bagian pertamanya dengan sub judul: Mimpi-Mimpi Lintang. Di buku ini rencananya Andrea akan mengisahkan tentang Arai, Lintang, A Ling, dan beberapa pertanyaan yang belum sempat terjawab di 3 buku terdahulu.
17	1	Hujan	Tere Liye	Gramedia	2016-01-01	public/img/books/book_1768215789_6964d4edea682.jpeg	t	Buku Hujan karya Tere Liye adalah novel fiksi ilmiah yang mengisahkan perjuangan hidup Lail, seorang gadis yang menjadi yatim piatu akibat bencana alam dahsyat (gunung berapi dan gempa) di masa depan tahun 2042. Bertahan hidup bersama pemuda bernama Esok di pengungsian, mereka terpisah, namun persahabatan dan cinta mereka bersemi di tengah latar belakang teknologi canggih, perubahan iklim, dan dilema menghapus kenangan menyakitkan untuk melanjutkan hidup.
13	1	Rantau 1 Muara	Ahmad Fuadi	Gramedia	2013-01-01	public/img/books/book_1768215865_6964d539969ae.jpeg	t	Rantau 1 Muara adalah buku ketiga dari trilogi Negeri 5 Menara karya Ahmad Fuadi, melanjutkan kisah Alif setelah lulus kuliah, berfokus pada pencarian jati diri, tempat berkarya (menjadi wartawan di AS), dan belahan jiwa (Dinara), di tengah krisis ekonomi 1998, dengan momen dramatis peristiwa 11 September 2001 yang menguji misi hidupnya hingga akhirnya ia menemukan "muara" sejati dan kembali ke Indonesia bersama Dinara, penuh motivasi tentang kegigihan dan pencarian makna hidup.
15	1	Bulan	Tere Liye	Gramedia	2015-01-01	public/img/books/book_1768215882_6964d54aa21ed.jpeg	f	Buku "Bulan" karya Tere Liye adalah novel fantasi petualangan remaja, sekuel dari "Bumi," yang melanjutkan kisah Raib, Seli, dan Ali saat mereka melakukan perjalanan ke Klan Matahari untuk diplomasi dan mengungkap misteri di balik kompetisi bunga matahari pertama, menghadapi tantangan berbahaya seperti hutan, monster, dan pengkhianatan Konsil Klan Matahari, sambil mengajarkan persahabatan, perjuangan, dan kemanusiaan, namun diwarnai pengorbanan tragis Ily.
12	1	Ranah 3 Warna	Ahmad Fuadi	Gramedia	2011-01-01	public/img/books/book_1768215957_6964d595b12ce.jpeg	f	Ranah 3 Warna karya Ahmad Fuadi adalah novel kedua dari trilogi Negeri 5 Menara, yang mengisahkan perjuangan Alif, seorang pemuda Minang, untuk meraih mimpi kuliah di luar negeri (Kanada) setelah lulus pesantren, dengan bermodalkan semangat dan dua mantra andalan: "Man Jadda Wajada" (siapa bersungguh-sungguh akan berhasil) dan "Man Shabara Zhafira" (siapa bersabar akan beruntung), menghadapi rintangan finansial dan pribadi, hingga akhirnya membuka jendela dunia dan merasakan pengalaman budaya berbeda di benua Amerika.
18	1	Pulang	Tere Liye	Republika	2015-01-01	public/img/books/pulang.jpeg	f	Buku Pulang merujuk pada beberapa novel populer, namun yang paling sering dibahas adalah karya Leila S. Chudori (fiksi sejarah eksil politik 1965) dan karya Tere Liye (aksi petualangan balas dendam). Karya Leila S. Chudori mengisahkan perjuangan eksil politik Indonesia di Paris pasca-1965, sementara Tere Liye menceritakan perjalanan Bujang, seorang yatim piatu tangguh yang membalas pengkhianatan dan mencari jati dirinya.
21	3	Filosofi Teras	Henry Manampiring	Kompas	2018-01-01	public/img/books/filosofi_teras.jpeg	t	Filosofi Teras karya Henry Manampiring adalah buku self-help populer yang mengadaptasi filsafat Stoisisme kuno untuk mengatasi masalah mental modern seperti kecemasan dan emosi negatif, mengajarkan cara berpikir rasional untuk fokus pada hal yang bisa dikendalikan (sikap dan persepsi) dan menerima hal yang tidak bisa dikendalikan, sehingga pembaca bisa hidup lebih tenang dan tangguh dengan bahasa ringan dan relevan.
8	1	Ayat-Ayat Cinta	Habiburrahman El Shirazy	Republika	2004-01-01	public/img/books/book_1768215301_6964d3052f828.jpeg	f	Ayat-Ayat Cinta adalah novel fenomenal karya Habiburrahman El Shirazy tentang Fahri, mahasiswa Indonesia di Mesir, yang menjalani kisah cinta kompleks dengan beberapa wanita (Maria, Aisha, Nurul), mengajarkan nilai-nilai keimanan, kesabaran, dan perjuangan menegakkan prinsip Islam di tengah cobaan berat, termasuk fitnah dan penjara, menjadikannya pelopor sastra Islami modern yang inspiratif.
19	1	Rindu	Tere Liye	Republika	2014-01-01	public/img/books/book_1768215742_6964d4bea2d76.jpeg	f	Rindu adalah novel karya Tere Liye yang mengisahkan perjalanan lima tokoh utama dengan masa lalu kelam di kapal uap Blitar Holland menuju Mekkah untuk menunaikan ibadah haji, menggali tema besar seperti cinta, kehilangan, kebencian, kemunafikan, dan penebusan dosa melalui kisah-kisah pribadi yang memilukan, dikemas dengan latar sejarah Indonesia masa kolonial, penuh pesan moral, namun juga menyelipkan humor dan bahasa yang mudah dipahami.
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
1	5	2025-12-20	2026-01-03	\N	2
2	12	2025-12-22	2026-01-05	\N	3
3	8	2025-12-15	2025-12-29	\N	4
4	25	2025-12-18	2026-01-01	\N	5
5	33	2025-12-28	2026-01-11	\N	6
6	41	2025-12-25	2026-01-08	\N	7
7	19	2025-12-10	2025-12-24	\N	8
8	55	2025-12-30	2026-01-13	\N	9
9	67	2025-12-29	2026-01-12	\N	10
10	72	2025-12-27	2026-01-10	\N	11
11	15	2025-11-01	2025-11-15	2025-11-14	2
12	28	2025-11-20	2025-12-04	2025-12-02	2
13	9	2025-10-15	2025-10-29	2025-10-28	3
14	44	2025-11-10	2025-11-24	2025-11-22	3
15	18	2025-11-05	2025-11-19	2025-11-18	4
16	31	2025-10-20	2025-11-03	2025-11-01	5
17	52	2025-11-15	2025-11-29	2025-11-27	6
18	63	2025-10-25	2025-11-08	2025-11-07	7
19	77	2025-11-08	2025-11-22	2025-11-20	8
20	84	2025-10-30	2025-11-13	2025-11-11	9
21	91	2025-11-12	2025-11-26	2025-11-25	10
22	3	2025-11-18	2025-12-02	2025-12-01	11
23	37	2025-10-01	2025-10-15	2025-10-20	2
24	48	2025-09-15	2025-09-29	2025-10-05	3
25	59	2025-10-10	2025-10-24	2025-10-31	4
26	66	2025-09-20	2025-10-04	2025-10-08	5
27	73	2025-10-05	2025-10-19	2025-10-25	6
28	80	2025-09-25	2025-10-09	2025-10-12	7
29	88	2025-10-12	2025-10-26	2025-11-02	8
30	95	2025-09-28	2025-10-12	2025-10-18	9
31	1	2026-01-02	2026-01-16	2026-01-02	13
33	69	2026-01-03	2026-01-17	\N	15
32	62	2026-01-03	2026-01-17	2026-01-03	15
34	94	2026-01-11	2026-01-25	\N	13
36	1	2026-01-11	2026-01-25	\N	13
35	7	2026-01-11	2026-01-25	2026-01-11	13
37	13	2026-01-11	2026-01-25	2026-01-11	13
38	24	2026-01-11	2026-01-25	\N	16
39	2	2026-01-12	2026-01-26	2026-01-12	17
41	42	2026-01-12	2026-01-26	\N	18
42	14	2026-01-12	2026-01-26	\N	18
40	35	2026-01-12	2026-01-26	2026-01-12	17
\.


--
-- Data for Name: bookreturn; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bookreturn (return_id, loan_id, return_date, penalty_id) FROM stdin;
1	11	2025-11-14	\N
2	12	2025-12-02	\N
3	13	2025-10-28	\N
4	14	2025-11-22	\N
5	15	2025-11-18	\N
6	16	2025-11-01	\N
7	17	2025-11-27	\N
8	18	2025-11-07	\N
9	19	2025-11-20	\N
10	20	2025-11-11	\N
11	21	2025-11-25	\N
12	22	2025-12-01	\N
13	23	2025-10-20	\N
14	24	2025-10-05	\N
15	25	2025-10-31	\N
16	26	2025-10-08	\N
17	27	2025-10-25	\N
18	28	2025-10-12	\N
19	29	2025-11-02	\N
20	30	2025-10-18	\N
21	31	2026-01-02	\N
22	32	2026-01-03	\N
23	35	2026-01-11	\N
24	37	2026-01-11	\N
25	39	2026-01-12	\N
26	40	2026-01-12	\N
\.


--
-- Data for Name: penalty; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.penalty (penalty_id, id, large_fines, paid, paid_date) FROM stdin;
1	2	10000	f	\N
2	3	12000	f	\N
3	4	14000	f	\N
4	5	8000	f	\N
5	6	12000	f	\N
6	7	6000	f	\N
7	8	14000	f	\N
8	9	12000	f	\N
\.


--
-- Data for Name: username; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.username (id, username, password, status, name, email, phone_number, google_id, auth_provider) FROM stdin;
1	admin	$2y$10$j26jMpvRnl5UjWbgfpoZnenGaVGBBWWgxgWTWuYRN2O0nBaj18CEe	admin	Administrator	admin@library.com	0811111111	\N	local
11	maul	$2y$10$t4PiQ34uw/SwwJBRDvfn3uYFzsvaNsxHlO/CRdqS7UupxT3QUyt4K	member	Imam Maulana	imammaul@gmail.com	0856987844	\N	local
12	ipemalisjakartaoffic	\N	member	IPEMALIS Jakarta	ipemalisjakarta.official@gmail.com	\N	109044630801560508018	google
2	andiwijaya	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Andi Wijaya	andi@mail.com	0812222222	\N	local
3	budisantoso	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Budi Santoso	budi@mail.com	0813333333	\N	local
4	citralestari	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Citra Lestari	citra@mail.com	0814444444	\N	local
5	dewianggraini	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Dewi Anggraini	dewi@mail.com	0815555555	\N	local
6	ekoprasetyo	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Eko Prasetyo	eko@mail.com	0816666666	\N	local
7	fajarhidayat	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Fajar Hidayat	fajar@mail.com	0817777777	\N	local
8	gitapermata	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Gita Permata	gita@mail.com	0818888888	\N	local
9	hadikurniawan	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Hadi Kurniawan	hadi@mail.com	0819999999	\N	local
10	indahsari	$2y$10$jZaN4ZwdhPLjt9Y4qnryHOAdqMhIlHdEiuq4vr/oLQGzeitcrB2BK	member	Indah Sari	indah@mail.com	0820000000	\N	local
14	razzan	$2y$10$MKiHz1.Al3ud9gfe4aiTfOhbvZxZW3cvJQlOtz1cq1jersskioA/C	member	Agil Razzan	agil.razzan@proton.me	085183019260	\N	local
15	aisyfillah	$2y$10$WTE5.v3wtNSPzfwc5bTuqOV0.YFvJNjfErOtqvubJIzT3k4irxLuS	member	Aisy Fillah Zuhdi	aisy.fillah@students.uag.ac.id	08123456789	\N	local
13	agilrazzan	\N	admin	2024 | Agil Razzan Murtadha	agil.razzan@students.uag.ac.id	\N	107523761430669448320	google
17	dhiya	$2y$10$a0iUUzAqcdB7dWCY2ou2QO.AmIDMcTxXwACFOVOtUjESlbg5XP0M6	member	dhiya	dhiya@gmail.com	08970897654678	\N	local
16	testeruser	$2y$10$cpEsprPVKOhSz2wyI5BFY.JgQvm7/On3B8nGFv9EoeU3EGrpIMzOC	admin	Tester user	test@mail.com	085666666363	\N	local
18	vitoputranto	$2y$10$QrYGfQ6UlLsX1GDVx6bXn.ue8vt8iqOEs/XaMOaO8cYh.SZwfeI9K	member	Belvito Raditya Dimas Rifai Putranto	vito06072004@gmail.com	082113383203	\N	local
19	alyadiva	$2y$10$bGifKoEiv1t.oFF7/EHn8u7PEeo0ous9XmhlpND2KZzADoIqUAdAy	admin	Alya Diva Ramadhani Agriwifi	divalyra@gmail.com	081294857316	\N	local
\.


--
-- Data for Name: waiting_list; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.waiting_list (waiting_id, book_id, id, request_date) FROM stdin;
1	8	2	2025-12-20
2	25	3	2025-12-22
3	5	4	2025-12-25
4	19	5	2025-12-23
5	72	6	2025-12-28
6	12	7	2025-12-26
7	33	8	2025-12-29
8	41	9	2025-12-27
9	55	10	2025-12-30
10	67	11	2025-12-31
11	8	10	2025-12-28
12	8	5	2025-12-30
13	25	6	2025-12-29
14	19	7	2025-12-28
15	5	11	2025-12-27
19	1	13	2026-01-11
20	5	13	2026-01-11
21	9	16	2026-01-11
22	1	17	2026-01-12
23	3	18	2026-01-12
\.


--
-- Name: penalty_penalty_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.penalty_penalty_id_seq', 1, false);


--
-- Name: waiting_list_waiting_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.waiting_list_waiting_id_seq', 23, true);


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
-- Name: idx_category_name; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_category_name ON public.bookcategory USING btree (category_name);


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
-- Name: waiting_list mahasiswa_delete_own_waiting; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY mahasiswa_delete_own_waiting ON public.waiting_list FOR DELETE TO mahasiswa USING ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.username)::text = CURRENT_USER))));


--
-- Name: waiting_list mahasiswa_insert_waiting; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY mahasiswa_insert_waiting ON public.waiting_list FOR INSERT TO mahasiswa WITH CHECK ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.username)::text = CURRENT_USER))));


--
-- Name: booklending mahasiswa_view_own_loans; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY mahasiswa_view_own_loans ON public.booklending FOR SELECT TO mahasiswa USING ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.username)::text = CURRENT_USER))));


--
-- Name: bookreturn mahasiswa_view_own_returns; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY mahasiswa_view_own_returns ON public.bookreturn FOR SELECT TO mahasiswa USING ((loan_id IN ( SELECT booklending.loan_id
   FROM public.booklending
  WHERE (booklending.id = ( SELECT username.id
           FROM public.username
          WHERE ((username.username)::text = CURRENT_USER))))));


--
-- Name: waiting_list mahasiswa_view_own_waiting; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY mahasiswa_view_own_waiting ON public.waiting_list FOR SELECT TO mahasiswa USING ((id = ( SELECT username.id
   FROM public.username
  WHERE ((username.username)::text = CURRENT_USER))));


--
-- Name: booklending staff_full_access_booklending; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY staff_full_access_booklending ON public.booklending TO staff_perpus USING (true) WITH CHECK (true);


--
-- Name: bookreturn staff_full_access_bookreturn; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY staff_full_access_bookreturn ON public.bookreturn TO staff_perpus USING (true) WITH CHECK (true);


--
-- Name: username staff_full_access_username; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY staff_full_access_username ON public.username TO staff_perpus USING (true) WITH CHECK (true);


--
-- Name: waiting_list staff_full_access_waiting; Type: POLICY; Schema: public; Owner: postgres
--

CREATE POLICY staff_full_access_waiting ON public.waiting_list TO staff_perpus USING (true) WITH CHECK (true);


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

GRANT USAGE ON SCHEMA public TO mahasiswa;
GRANT USAGE ON SCHEMA public TO staff_perpus;


--
-- Name: TABLE book; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.book TO staff_perpus;
GRANT SELECT ON TABLE public.book TO mahasiswa;


--
-- Name: TABLE bookcategory; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.bookcategory TO staff_perpus;
GRANT SELECT ON TABLE public.bookcategory TO mahasiswa;


--
-- Name: TABLE booklending; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.booklending TO staff_perpus;
GRANT SELECT ON TABLE public.booklending TO mahasiswa;


--
-- Name: TABLE bookreturn; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.bookreturn TO staff_perpus;
GRANT SELECT ON TABLE public.bookreturn TO mahasiswa;


--
-- Name: TABLE penalty; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.penalty TO staff_perpus;


--
-- Name: TABLE username; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.username TO staff_perpus;
GRANT SELECT ON TABLE public.username TO mahasiswa;


--
-- Name: SEQUENCE penalty_penalty_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT USAGE ON SEQUENCE public.penalty_penalty_id_seq TO staff_perpus;


--
-- Name: TABLE waiting_list; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,INSERT,UPDATE ON TABLE public.waiting_list TO staff_perpus;
GRANT SELECT ON TABLE public.waiting_list TO mahasiswa;


--
-- Name: SEQUENCE waiting_list_waiting_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT USAGE ON SEQUENCE public.waiting_list_waiting_id_seq TO staff_perpus;
GRANT USAGE ON SEQUENCE public.waiting_list_waiting_id_seq TO mahasiswa;


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

\unrestrict JjOQosjescbzLEVULMUqXdKxRQclh7mVlHHCDzyDAho5f1fofD5ZutwjoCGB6ks

