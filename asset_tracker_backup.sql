--
-- PostgreSQL database dump
--

\restrict 0HskOECJU7C9KUc1Gdq0de0RLC6loibN8NHv5M1X6F7xW5CqxzyxI9iKL46EXPN

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: asset_documents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_documents (
    id bigint NOT NULL,
    asset_id bigint NOT NULL,
    file_path character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    file_type character varying(255) NOT NULL,
    document_type character varying(255) NOT NULL,
    mime_type character varying(255) NOT NULL,
    file_size integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.asset_documents OWNER TO postgres;

--
-- Name: asset_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_documents_id_seq OWNER TO postgres;

--
-- Name: asset_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_documents_id_seq OWNED BY public.asset_documents.id;


--
-- Name: asset_mail_message; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_mail_message (
    id bigint NOT NULL,
    asset_id bigint NOT NULL,
    mail_message_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.asset_mail_message OWNER TO postgres;

--
-- Name: asset_mail_message_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_mail_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_mail_message_id_seq OWNER TO postgres;

--
-- Name: asset_mail_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_mail_message_id_seq OWNED BY public.asset_mail_message.id;


--
-- Name: assets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.assets (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    asset_type character varying(255) DEFAULT 'Car'::character varying NOT NULL,
    name character varying(255) NOT NULL,
    acquisition_date date NOT NULL,
    acquisition_cost numeric(15,2) NOT NULL,
    current_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'Active'::character varying NOT NULL,
    description text,
    registration_number character varying(255),
    registration_due_date date,
    insurance_company character varying(255),
    insurance_due_date date,
    insurance_amount numeric(15,2),
    vin_number character varying(255),
    mileage integer,
    fuel_type character varying(255),
    service_due_date date,
    vic_roads_updated boolean DEFAULT false NOT NULL,
    address text,
    square_footage integer,
    council_rates_amount numeric(15,2),
    council_rates_due_date date,
    owners_corp_amount numeric(15,2),
    owners_corp_due_date date,
    land_tax_amount numeric(15,2),
    land_tax_due_date date,
    sro_updated boolean DEFAULT false NOT NULL,
    real_estate_percentage numeric(5,2),
    rental_income numeric(15,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    user_id bigint,
    depreciation_method character varying(255),
    useful_life_years integer,
    residual_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    accumulated_depreciation numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    book_value numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    is_depreciable boolean DEFAULT false NOT NULL,
    depreciation_account_id bigint,
    disposal_date date,
    disposal_amount numeric(15,2),
    CONSTRAINT assets_asset_type_check CHECK (((asset_type)::text = ANY ((ARRAY['Car'::character varying, 'House Owned'::character varying, 'House Rented'::character varying, 'Warehouse'::character varying, 'Land'::character varying, 'Office'::character varying, 'Shop'::character varying, 'Real Estate'::character varying, 'Suite'::character varying])::text[]))),
    CONSTRAINT assets_fuel_type_check CHECK (((fuel_type)::text = ANY ((ARRAY['Petrol'::character varying, 'Diesel'::character varying, 'Electric'::character varying, 'Hybrid'::character varying])::text[]))),
    CONSTRAINT assets_status_check CHECK (((status)::text = ANY ((ARRAY['Active'::character varying, 'Inactive'::character varying, 'Sold'::character varying, 'Under Maintenance'::character varying])::text[])))
);


ALTER TABLE public.assets OWNER TO postgres;

--
-- Name: assets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.assets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assets_id_seq OWNER TO postgres;

--
-- Name: assets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.assets_id_seq OWNED BY public.assets.id;


--
-- Name: bank_accounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bank_accounts (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    bank_name character varying(255) NOT NULL,
    bsb character varying(6) NOT NULL,
    account_number character varying(255) NOT NULL,
    nickname character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.bank_accounts OWNER TO postgres;

--
-- Name: bank_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bank_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bank_accounts_id_seq OWNER TO postgres;

--
-- Name: bank_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bank_accounts_id_seq OWNED BY public.bank_accounts.id;


--
-- Name: bank_statement_entries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bank_statement_entries (
    id bigint NOT NULL,
    bank_account_id bigint NOT NULL,
    date date NOT NULL,
    amount numeric(15,2) NOT NULL,
    description character varying(255),
    transaction_type character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    transaction_id bigint
);


ALTER TABLE public.bank_statement_entries OWNER TO postgres;

--
-- Name: bank_statement_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bank_statement_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bank_statement_entries_id_seq OWNER TO postgres;

--
-- Name: bank_statement_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bank_statement_entries_id_seq OWNED BY public.bank_statement_entries.id;


--
-- Name: business_entities; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.business_entities (
    id bigint NOT NULL,
    legal_name character varying(255) NOT NULL,
    entity_type character varying(255) DEFAULT 'Company'::character varying NOT NULL,
    user_id bigint NOT NULL,
    abn character varying(255),
    acn character varying(255),
    status character varying(255) DEFAULT 'Active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    person_id bigint,
    trading_name character varying(255),
    tfn character varying(255),
    corporate_key character varying(255),
    registered_address character varying(255) NOT NULL,
    registered_email character varying(255) NOT NULL,
    phone_number character varying(15) NOT NULL,
    asic_renewal_date date,
    creation_date date,
    trust_type character varying(255),
    trust_establishment_date date,
    trust_deed_date date,
    trust_deed_reference character varying(255),
    trust_vesting_date date,
    trust_vesting_conditions text,
    appointor_person_id bigint,
    appointor_entity_id bigint,
    CONSTRAINT business_entities_entity_type_check CHECK (((entity_type)::text = ANY ((ARRAY['Company'::character varying, 'Trust'::character varying, 'Sole Trader'::character varying, 'Partnership'::character varying])::text[]))),
    CONSTRAINT business_entities_status_check CHECK (((status)::text = ANY ((ARRAY['Active'::character varying, 'Inactive'::character varying, 'Deregistered'::character varying])::text[]))),
    CONSTRAINT business_entities_trust_type_check CHECK (((trust_type)::text = ANY ((ARRAY['Discretionary'::character varying, 'Unit'::character varying, 'Fixed'::character varying, 'Testamentary'::character varying, 'Charitable'::character varying])::text[])))
);


ALTER TABLE public.business_entities OWNER TO postgres;

--
-- Name: business_entities_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.business_entities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.business_entities_id_seq OWNER TO postgres;

--
-- Name: business_entities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.business_entities_id_seq OWNED BY public.business_entities.id;


--
-- Name: business_entity_mail_message; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.business_entity_mail_message (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    mail_message_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.business_entity_mail_message OWNER TO postgres;

--
-- Name: business_entity_mail_message_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.business_entity_mail_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.business_entity_mail_message_id_seq OWNER TO postgres;

--
-- Name: business_entity_mail_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.business_entity_mail_message_id_seq OWNED BY public.business_entity_mail_message.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: chart_of_accounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.chart_of_accounts (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    account_code character varying(20) NOT NULL,
    account_name character varying(255) NOT NULL,
    account_type character varying(255) NOT NULL,
    account_category character varying(50) NOT NULL,
    parent_account_id bigint,
    is_active boolean DEFAULT true NOT NULL,
    description text,
    opening_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    current_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chart_of_accounts_account_type_check CHECK (((account_type)::text = ANY ((ARRAY['asset'::character varying, 'liability'::character varying, 'equity'::character varying, 'income'::character varying, 'expense'::character varying])::text[])))
);


ALTER TABLE public.chart_of_accounts OWNER TO postgres;

--
-- Name: chart_of_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.chart_of_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.chart_of_accounts_id_seq OWNER TO postgres;

--
-- Name: chart_of_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.chart_of_accounts_id_seq OWNED BY public.chart_of_accounts.id;


--
-- Name: contact_lists; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.contact_lists (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    first_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    gender character varying(255) NOT NULL,
    email character varying(255),
    phone_no character varying(255),
    mobile_no character varying(255),
    address text,
    zip_code character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT contact_lists_gender_check CHECK (((gender)::text = ANY ((ARRAY['male'::character varying, 'female'::character varying, 'other'::character varying])::text[])))
);


ALTER TABLE public.contact_lists OWNER TO postgres;

--
-- Name: contact_lists_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.contact_lists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.contact_lists_id_seq OWNER TO postgres;

--
-- Name: contact_lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.contact_lists_id_seq OWNED BY public.contact_lists.id;


--
-- Name: depreciation_schedules; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.depreciation_schedules (
    id bigint NOT NULL,
    asset_id bigint NOT NULL,
    financial_year character varying(9) NOT NULL,
    depreciation_amount numeric(15,2) NOT NULL,
    accumulated_depreciation numeric(15,2) NOT NULL,
    book_value numeric(15,2) NOT NULL,
    is_posted boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.depreciation_schedules OWNER TO postgres;

--
-- Name: depreciation_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.depreciation_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.depreciation_schedules_id_seq OWNER TO postgres;

--
-- Name: depreciation_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.depreciation_schedules_id_seq OWNED BY public.depreciation_schedules.id;


--
-- Name: documents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.documents (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    file_name character varying(255) NOT NULL,
    path character varying(255) NOT NULL,
    type character varying(255),
    description character varying(255),
    filetype character varying(255),
    user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    asset_id bigint
);


ALTER TABLE public.documents OWNER TO postgres;

--
-- Name: documents_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.documents_id_seq OWNER TO postgres;

--
-- Name: documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.documents_id_seq OWNED BY public.documents.id;


--
-- Name: email_drafts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.email_drafts (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    from_email character varying(255) NOT NULL,
    to_email character varying(255) NOT NULL,
    cc_email character varying(255),
    bcc_email character varying(255),
    subject character varying(255) NOT NULL,
    message text NOT NULL,
    attachments json,
    business_entity_id bigint,
    template_id bigint,
    scheduled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.email_drafts OWNER TO postgres;

--
-- Name: email_drafts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.email_drafts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.email_drafts_id_seq OWNER TO postgres;

--
-- Name: email_drafts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.email_drafts_id_seq OWNED BY public.email_drafts.id;


--
-- Name: email_templates; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.email_templates (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    subject character varying(255) NOT NULL,
    description text NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.email_templates OWNER TO postgres;

--
-- Name: email_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.email_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.email_templates_id_seq OWNER TO postgres;

--
-- Name: email_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.email_templates_id_seq OWNED BY public.email_templates.id;


--
-- Name: emails; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.emails (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255),
    email_signature text,
    display_name character varying(255),
    status character varying(255),
    user_id bigint,
    type character varying(255),
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.emails OWNER TO postgres;

--
-- Name: emails_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.emails_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.emails_id_seq OWNER TO postgres;

--
-- Name: emails_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.emails_id_seq OWNED BY public.emails.id;


--
-- Name: entity_person; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.entity_person (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    person_id bigint,
    entity_trustee_id bigint,
    role character varying(255) NOT NULL,
    appointment_date date NOT NULL,
    resignation_date date,
    role_status character varying(255) NOT NULL,
    shares_percentage numeric(5,2),
    authority_level character varying(255),
    asic_updated boolean DEFAULT false NOT NULL,
    asic_due_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    appointor_entity_id bigint,
    CONSTRAINT entity_person_authority_level_check CHECK (((authority_level)::text = ANY ((ARRAY['Full'::character varying, 'Limited'::character varying])::text[]))),
    CONSTRAINT entity_person_role_check CHECK (((role)::text = ANY ((ARRAY['Director'::character varying, 'Secretary'::character varying, 'Shareholder'::character varying, 'Trustee'::character varying, 'Beneficiary'::character varying, 'Settlor'::character varying, 'Owner'::character varying, 'Appointor'::character varying])::text[])))
);


ALTER TABLE public.entity_person OWNER TO postgres;

--
-- Name: entity_person_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.entity_person_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.entity_person_id_seq OWNER TO postgres;

--
-- Name: entity_person_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.entity_person_id_seq OWNED BY public.entity_person.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: invoice_lines; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.invoice_lines (
    id bigint NOT NULL,
    invoice_id bigint NOT NULL,
    description character varying(255) NOT NULL,
    quantity numeric(15,4) DEFAULT '1'::numeric NOT NULL,
    unit_price numeric(15,4) DEFAULT '0'::numeric NOT NULL,
    line_total numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    gst_rate numeric(5,4) DEFAULT 0.1 NOT NULL,
    account_code character varying(20),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.invoice_lines OWNER TO postgres;

--
-- Name: invoice_lines_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.invoice_lines_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.invoice_lines_id_seq OWNER TO postgres;

--
-- Name: invoice_lines_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.invoice_lines_id_seq OWNED BY public.invoice_lines.id;


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.invoices (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    invoice_number character varying(50) NOT NULL,
    issue_date date NOT NULL,
    due_date date,
    customer_name character varying(255) NOT NULL,
    reference character varying(255),
    subtotal numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    gst_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    currency character varying(3) DEFAULT 'AUD'::character varying NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    is_posted boolean DEFAULT false NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT invoices_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'approved'::character varying, 'paid'::character varying, 'void'::character varying])::text[])))
);


ALTER TABLE public.invoices OWNER TO postgres;

--
-- Name: invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.invoices_id_seq OWNER TO postgres;

--
-- Name: invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.invoices_id_seq OWNED BY public.invoices.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: journal_entries; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.journal_entries (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    entry_date date NOT NULL,
    reference_number character varying(50) NOT NULL,
    description text NOT NULL,
    total_debit numeric(15,2) NOT NULL,
    total_credit numeric(15,2) NOT NULL,
    is_posted boolean DEFAULT false NOT NULL,
    created_by bigint NOT NULL,
    source_type character varying(255),
    source_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.journal_entries OWNER TO postgres;

--
-- Name: journal_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.journal_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.journal_entries_id_seq OWNER TO postgres;

--
-- Name: journal_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.journal_entries_id_seq OWNED BY public.journal_entries.id;


--
-- Name: journal_lines; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.journal_lines (
    id bigint NOT NULL,
    journal_entry_id bigint NOT NULL,
    chart_of_account_id bigint NOT NULL,
    debit_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    credit_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    description text,
    reference character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    tracking_category_id bigint,
    tracking_sub_category_id bigint
);


ALTER TABLE public.journal_lines OWNER TO postgres;

--
-- Name: journal_lines_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.journal_lines_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.journal_lines_id_seq OWNER TO postgres;

--
-- Name: journal_lines_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.journal_lines_id_seq OWNED BY public.journal_lines.id;


--
-- Name: leases; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.leases (
    id bigint NOT NULL,
    asset_id bigint NOT NULL,
    tenant_id bigint,
    rental_amount numeric(10,2) NOT NULL,
    payment_frequency character varying(255) DEFAULT 'Monthly'::character varying NOT NULL,
    start_date date NOT NULL,
    end_date date,
    terms text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.leases OWNER TO postgres;

--
-- Name: leases_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.leases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.leases_id_seq OWNER TO postgres;

--
-- Name: leases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.leases_id_seq OWNED BY public.leases.id;


--
-- Name: mail_attachments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mail_attachments (
    id bigint NOT NULL,
    mail_message_id bigint NOT NULL,
    filename character varying(255) NOT NULL,
    content_type character varying(255),
    file_size bigint DEFAULT '0'::bigint NOT NULL,
    storage_path character varying(255) NOT NULL,
    is_inline boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.mail_attachments OWNER TO postgres;

--
-- Name: mail_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mail_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mail_attachments_id_seq OWNER TO postgres;

--
-- Name: mail_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mail_attachments_id_seq OWNED BY public.mail_attachments.id;


--
-- Name: mail_label_mail_message; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mail_label_mail_message (
    mail_label_id bigint NOT NULL,
    mail_message_id bigint NOT NULL
);


ALTER TABLE public.mail_label_mail_message OWNER TO postgres;

--
-- Name: mail_labels; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mail_labels (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    color character varying(255),
    type character varying(255) DEFAULT 'system'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.mail_labels OWNER TO postgres;

--
-- Name: mail_labels_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mail_labels_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mail_labels_id_seq OWNER TO postgres;

--
-- Name: mail_labels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mail_labels_id_seq OWNED BY public.mail_labels.id;


--
-- Name: mail_messages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.mail_messages (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    gmail_id character varying(255),
    message_id character varying(255),
    subject character varying(255),
    sender_name character varying(255),
    sender_email character varying(255),
    recipients text,
    sent_date timestamp(0) without time zone,
    html_content text,
    text_content text,
    status character varying(255) DEFAULT 'new'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    source_type character varying(255),
    source_path character varying(255),
    source_hash character varying(255)
);


ALTER TABLE public.mail_messages OWNER TO postgres;

--
-- Name: mail_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mail_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mail_messages_id_seq OWNER TO postgres;

--
-- Name: mail_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mail_messages_id_seq OWNED BY public.mail_messages.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notes (
    id bigint NOT NULL,
    content text NOT NULL,
    business_entity_id bigint NOT NULL,
    user_id bigint NOT NULL,
    is_reminder boolean DEFAULT false NOT NULL,
    reminder_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    asset_id bigint
);


ALTER TABLE public.notes OWNER TO postgres;

--
-- Name: notes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notes_id_seq OWNER TO postgres;

--
-- Name: notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notes_id_seq OWNED BY public.notes.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: persons; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.persons (
    id bigint NOT NULL,
    first_name character varying(255) NOT NULL,
    last_name character varying(255) NOT NULL,
    email character varying(255),
    phone_number character varying(255),
    address text,
    tfn character varying(9),
    identification_number character varying(255),
    nationality character varying(255),
    status character varying(255) DEFAULT 'Active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    abn character varying(11),
    CONSTRAINT persons_status_check CHECK (((status)::text = ANY ((ARRAY['Active'::character varying, 'Inactive'::character varying])::text[])))
);


ALTER TABLE public.persons OWNER TO postgres;

--
-- Name: persons_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.persons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.persons_id_seq OWNER TO postgres;

--
-- Name: persons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.persons_id_seq OWNED BY public.persons.id;


--
-- Name: reminders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reminders (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    content text NOT NULL,
    reminder_date timestamp(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    repeat_type character varying(255) DEFAULT 'none'::character varying NOT NULL,
    repeat_end_date date,
    next_due_date date,
    business_entity_id bigint,
    asset_id bigint,
    category character varying(255),
    notes text,
    is_completed boolean DEFAULT false NOT NULL,
    completed_at timestamp(0) without time zone,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    recurrence character varying(255) DEFAULT 'None'::character varying NOT NULL,
    recurrence_interval integer,
    next_reminder_date date,
    reminder_type character varying(255),
    reminder_id bigint,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reminders_recurrence_check CHECK (((recurrence)::text = ANY ((ARRAY['None'::character varying, 'Monthly'::character varying, 'Quarterly'::character varying, 'Annually'::character varying, 'Custom'::character varying])::text[])))
);


ALTER TABLE public.reminders OWNER TO postgres;

--
-- Name: reminders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reminders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reminders_id_seq OWNER TO postgres;

--
-- Name: reminders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reminders_id_seq OWNED BY public.reminders.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: tenants; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tenants (
    id bigint NOT NULL,
    asset_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    phone character varying(255),
    address text,
    move_in_date date,
    move_out_date date,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.tenants OWNER TO postgres;

--
-- Name: tenants_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tenants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tenants_id_seq OWNER TO postgres;

--
-- Name: tenants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tenants_id_seq OWNED BY public.tenants.id;


--
-- Name: tracking_categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tracking_categories (
    id bigint NOT NULL,
    business_entity_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.tracking_categories OWNER TO postgres;

--
-- Name: tracking_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tracking_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tracking_categories_id_seq OWNER TO postgres;

--
-- Name: tracking_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tracking_categories_id_seq OWNED BY public.tracking_categories.id;


--
-- Name: tracking_sub_categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tracking_sub_categories (
    id bigint NOT NULL,
    tracking_category_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.tracking_sub_categories OWNER TO postgres;

--
-- Name: tracking_sub_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tracking_sub_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tracking_sub_categories_id_seq OWNER TO postgres;

--
-- Name: tracking_sub_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tracking_sub_categories_id_seq OWNED BY public.tracking_sub_categories.id;


--
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    bank_account_id bigint NOT NULL,
    business_entity_id bigint,
    date date NOT NULL,
    amount numeric(15,2) NOT NULL,
    description character varying(255),
    transaction_type character varying(255),
    gst_amount numeric(15,2),
    gst_status character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    receipt_path character varying(255),
    related_entity_id bigint,
    tracking_category_id bigint,
    tracking_sub_category_id bigint
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transactions_id_seq OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email text NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    two_factor_secret character varying(255),
    two_factor_backup_codes text,
    two_factor_enabled boolean DEFAULT false NOT NULL,
    password_changed_at timestamp(0) without time zone,
    last_login_at timestamp(0) without time zone,
    last_login_ip character varying(255)
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: asset_documents id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents ALTER COLUMN id SET DEFAULT nextval('public.asset_documents_id_seq'::regclass);


--
-- Name: asset_mail_message id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_mail_message ALTER COLUMN id SET DEFAULT nextval('public.asset_mail_message_id_seq'::regclass);


--
-- Name: assets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets ALTER COLUMN id SET DEFAULT nextval('public.assets_id_seq'::regclass);


--
-- Name: bank_accounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_accounts ALTER COLUMN id SET DEFAULT nextval('public.bank_accounts_id_seq'::regclass);


--
-- Name: bank_statement_entries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_statement_entries ALTER COLUMN id SET DEFAULT nextval('public.bank_statement_entries_id_seq'::regclass);


--
-- Name: business_entities id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities ALTER COLUMN id SET DEFAULT nextval('public.business_entities_id_seq'::regclass);


--
-- Name: business_entity_mail_message id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entity_mail_message ALTER COLUMN id SET DEFAULT nextval('public.business_entity_mail_message_id_seq'::regclass);


--
-- Name: chart_of_accounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chart_of_accounts ALTER COLUMN id SET DEFAULT nextval('public.chart_of_accounts_id_seq'::regclass);


--
-- Name: contact_lists id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contact_lists ALTER COLUMN id SET DEFAULT nextval('public.contact_lists_id_seq'::regclass);


--
-- Name: depreciation_schedules id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.depreciation_schedules ALTER COLUMN id SET DEFAULT nextval('public.depreciation_schedules_id_seq'::regclass);


--
-- Name: documents id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.documents ALTER COLUMN id SET DEFAULT nextval('public.documents_id_seq'::regclass);


--
-- Name: email_drafts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_drafts ALTER COLUMN id SET DEFAULT nextval('public.email_drafts_id_seq'::regclass);


--
-- Name: email_templates id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_templates ALTER COLUMN id SET DEFAULT nextval('public.email_templates_id_seq'::regclass);


--
-- Name: emails id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.emails ALTER COLUMN id SET DEFAULT nextval('public.emails_id_seq'::regclass);


--
-- Name: entity_person id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entity_person ALTER COLUMN id SET DEFAULT nextval('public.entity_person_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: invoice_lines id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.invoice_lines ALTER COLUMN id SET DEFAULT nextval('public.invoice_lines_id_seq'::regclass);


--
-- Name: invoices id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.invoices ALTER COLUMN id SET DEFAULT nextval('public.invoices_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: journal_entries id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_entries ALTER COLUMN id SET DEFAULT nextval('public.journal_entries_id_seq'::regclass);


--
-- Name: journal_lines id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_lines ALTER COLUMN id SET DEFAULT nextval('public.journal_lines_id_seq'::regclass);


--
-- Name: leases id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leases ALTER COLUMN id SET DEFAULT nextval('public.leases_id_seq'::regclass);


--
-- Name: mail_attachments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_attachments ALTER COLUMN id SET DEFAULT nextval('public.mail_attachments_id_seq'::regclass);


--
-- Name: mail_labels id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_labels ALTER COLUMN id SET DEFAULT nextval('public.mail_labels_id_seq'::regclass);


--
-- Name: mail_messages id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_messages ALTER COLUMN id SET DEFAULT nextval('public.mail_messages_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notes ALTER COLUMN id SET DEFAULT nextval('public.notes_id_seq'::regclass);


--
-- Name: persons id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.persons ALTER COLUMN id SET DEFAULT nextval('public.persons_id_seq'::regclass);


--
-- Name: reminders id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reminders ALTER COLUMN id SET DEFAULT nextval('public.reminders_id_seq'::regclass);


--
-- Name: tenants id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tenants ALTER COLUMN id SET DEFAULT nextval('public.tenants_id_seq'::regclass);


--
-- Name: tracking_categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tracking_categories ALTER COLUMN id SET DEFAULT nextval('public.tracking_categories_id_seq'::regclass);


--
-- Name: tracking_sub_categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tracking_sub_categories ALTER COLUMN id SET DEFAULT nextval('public.tracking_sub_categories_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: asset_documents; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_documents (id, asset_id, file_path, file_name, file_type, document_type, mime_type, file_size, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: asset_mail_message; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_mail_message (id, asset_id, mail_message_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: assets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.assets (id, business_entity_id, asset_type, name, acquisition_date, acquisition_cost, current_value, status, description, registration_number, registration_due_date, insurance_company, insurance_due_date, insurance_amount, vin_number, mileage, fuel_type, service_due_date, vic_roads_updated, address, square_footage, council_rates_amount, council_rates_due_date, owners_corp_amount, owners_corp_due_date, land_tax_amount, land_tax_due_date, sro_updated, real_estate_percentage, rental_income, created_at, updated_at, user_id, depreciation_method, useful_life_years, residual_value, accumulated_depreciation, book_value, is_depreciable, depreciation_account_id, disposal_date, disposal_amount) FROM stdin;
\.


--
-- Data for Name: bank_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bank_accounts (id, business_entity_id, bank_name, bsb, account_number, nickname, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: bank_statement_entries; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bank_statement_entries (id, bank_account_id, date, amount, description, transaction_type, created_at, updated_at, transaction_id) FROM stdin;
\.


--
-- Data for Name: business_entities; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.business_entities (id, legal_name, entity_type, user_id, abn, acn, status, created_at, updated_at, person_id, trading_name, tfn, corporate_key, registered_address, registered_email, phone_number, asic_renewal_date, creation_date, trust_type, trust_establishment_date, trust_deed_date, trust_deed_reference, trust_vesting_date, trust_vesting_conditions, appointor_person_id, appointor_entity_id) FROM stdin;
\.


--
-- Data for Name: business_entity_mail_message; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.business_entity_mail_message (id, business_entity_id, mail_message_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: chart_of_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.chart_of_accounts (id, business_entity_id, account_code, account_name, account_type, account_category, parent_account_id, is_active, description, opening_balance, current_balance, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: contact_lists; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.contact_lists (id, business_entity_id, first_name, last_name, gender, email, phone_no, mobile_no, address, zip_code, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: depreciation_schedules; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.depreciation_schedules (id, asset_id, financial_year, depreciation_amount, accumulated_depreciation, book_value, is_posted, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: documents; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.documents (id, business_entity_id, file_name, path, type, description, filetype, user_id, created_at, updated_at, asset_id) FROM stdin;
\.


--
-- Data for Name: email_drafts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.email_drafts (id, user_id, from_email, to_email, cc_email, bcc_email, subject, message, attachments, business_entity_id, template_id, scheduled_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: email_templates; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.email_templates (id, name, subject, description, user_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: emails; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.emails (id, email, password, email_signature, display_name, status, user_id, type, error_message, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: entity_person; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.entity_person (id, business_entity_id, person_id, entity_trustee_id, role, appointment_date, resignation_date, role_status, shares_percentage, authority_level, asic_updated, asic_due_date, created_at, updated_at, appointor_entity_id) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: invoice_lines; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.invoice_lines (id, invoice_id, description, quantity, unit_price, line_total, gst_rate, account_code, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: invoices; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.invoices (id, business_entity_id, invoice_number, issue_date, due_date, customer_name, reference, subtotal, gst_amount, total_amount, currency, status, is_posted, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: journal_entries; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.journal_entries (id, business_entity_id, entry_date, reference_number, description, total_debit, total_credit, is_posted, created_by, source_type, source_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: journal_lines; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.journal_lines (id, journal_entry_id, chart_of_account_id, debit_amount, credit_amount, description, reference, created_at, updated_at, tracking_category_id, tracking_sub_category_id) FROM stdin;
\.


--
-- Data for Name: leases; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.leases (id, asset_id, tenant_id, rental_amount, payment_frequency, start_date, end_date, terms, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: mail_attachments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mail_attachments (id, mail_message_id, filename, content_type, file_size, storage_path, is_inline, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: mail_label_mail_message; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mail_label_mail_message (mail_label_id, mail_message_id) FROM stdin;
\.


--
-- Data for Name: mail_labels; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mail_labels (id, user_id, name, color, type, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: mail_messages; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.mail_messages (id, user_id, gmail_id, message_id, subject, sender_name, sender_email, recipients, sent_date, html_content, text_content, status, created_at, updated_at, source_type, source_path, source_hash) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_03_09_011225_create_persons_table	1
5	2025_03_09_011226_create_business_entities_table	1
6	2025_03_09_011227_create_entity_person_table	1
7	2025_03_09_011228_add_person_id_to_business_entities_table	1
8	2025_03_09_011229_create_tracking_categories_table	1
9	2025_03_09_011230_create_tracking_sub_categories_table	1
10	2025_03_09_011252_create_notes_table	1
11	2025_03_09_021734_create_assets_table	1
12	2025_03_10_052211_update_business_entities_table_schema	1
13	2025_03_10_075745_add_user_id_to_assets_table	1
14	2025_03_11_065629_update_persons_table_remove_dob_add_abn	1
15	2025_03_15_053607_create_bank_accounts_table	1
16	2025_03_15_062006_create_transactions_table	1
17	2025_03_16_095525_create_bank_statement_entries_table	1
18	2025_03_18_095536_add_business_entity_id_to_transactions_table	1
19	2025_03_18_102011_add_transaction_id_to_bank_statement_entries	1
20	2025_03_21_091624_add_receipt_path_to_transactions_table	1
21	2025_04_11_044339_create_asset_documents_table	1
22	2025_04_12_040000_create_documents_table	1
23	2025_04_12_043953_create_tenants_table	1
24	2025_04_12_044054_create_leases_table	1
25	2025_04_12_044728_add_asset_id_to_documents_table	1
26	2025_04_13_035934_add_asset_id_to_notes_table	1
27	2025_04_13_040431_update_business_entities_add_creation_fields	1
28	2025_04_13_040458_create_reminders_table_combined	1
29	2025_04_18_add_missing_fields_to_persons_table	1
30	2025_04_20_061727_remove_unique_constraints_from_entity_person_table	1
31	2025_04_20_063000_recreate_entity_person_table_without_constraints	1
32	2025_04_20_064500_drop_all_constraints_entity_person_table	1
33	2025_07_07_041521_create_emails_table	1
34	2025_08_28_000001_create_mail_messages_table	1
35	2025_08_28_000001_drop_unique_on_emails_and_phone	1
36	2025_08_28_000002_create_business_entity_mail_message_table	1
37	2025_08_28_000002_create_mail_attachments_table	1
38	2025_08_28_000003_create_asset_mail_message_table	1
39	2025_08_28_000003_create_mail_labels_table	1
40	2025_08_28_000004_create_mail_label_mail_message_table	1
41	2025_08_28_000005_add_source_fields_to_mail_messages_table	1
42	2025_08_28_000006_prevent_duplicates_in_mail_messages	1
43	2025_08_28_074029_create_contact_lists_table	1
44	2025_08_29_021517_create_email_templates_table	1
45	2025_08_29_021536_create_email_drafts_table	1
46	2025_09_04_054241_create_chart_of_accounts_table	1
47	2025_09_04_054247_create_journal_entries_table	1
48	2025_09_04_054308_add_depreciation_fields_to_assets_table	1
49	2025_09_04_054345_create_depreciation_schedules_table	1
50	2025_09_04_054349_add_suite_to_asset_types	1
51	2025_09_04_083005_adjust_unique_index_on_chart_of_accounts	1
52	2025_09_04_083240_create_invoices_table	1
53	2025_09_04_083247_create_invoice_lines_table	1
54	2025_09_04_103431_add_related_entity_id_to_transactions_table	1
55	2025_09_05_000001_add_tracking_categories_to_transactions	1
56	2025_09_05_000002_add_tracking_categories_to_journal_lines	1
57	2025_09_05_063517_add_two_factor_fields_to_users_table	1
58	2025_09_08_015424_add_trust_fields_to_business_entities_table	1
59	2025_09_08_035211_add_appointor_support_to_entity_person_table	1
60	2026_03_19_075738_expand_email_column_for_encryption_in_users_table	1
\.


--
-- Data for Name: notes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notes (id, content, business_entity_id, user_id, is_reminder, reminder_date, created_at, updated_at, asset_id) FROM stdin;
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: persons; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.persons (id, first_name, last_name, email, phone_number, address, tfn, identification_number, nationality, status, created_at, updated_at, abn) FROM stdin;
\.


--
-- Data for Name: reminders; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reminders (id, title, content, reminder_date, status, repeat_type, repeat_end_date, next_due_date, business_entity_id, asset_id, category, notes, is_completed, completed_at, priority, recurrence, recurrence_interval, next_reminder_date, reminder_type, reminder_id, user_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: tenants; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tenants (id, asset_id, name, email, phone, address, move_in_date, move_out_date, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: tracking_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tracking_categories (id, business_entity_id, name, description, is_active, sort_order, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: tracking_sub_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tracking_sub_categories (id, tracking_category_id, name, description, is_active, sort_order, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transactions (id, bank_account_id, business_entity_id, date, amount, description, transaction_type, gst_amount, gst_status, created_at, updated_at, receipt_path, related_entity_id, tracking_category_id, tracking_sub_category_id) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, two_factor_secret, two_factor_backup_codes, two_factor_enabled, password_changed_at, last_login_at, last_login_ip) FROM stdin;
\.


--
-- Name: asset_documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_documents_id_seq', 1, false);


--
-- Name: asset_mail_message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_mail_message_id_seq', 1, false);


--
-- Name: assets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.assets_id_seq', 1, false);


--
-- Name: bank_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bank_accounts_id_seq', 1, false);


--
-- Name: bank_statement_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bank_statement_entries_id_seq', 1, false);


--
-- Name: business_entities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.business_entities_id_seq', 1, false);


--
-- Name: business_entity_mail_message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.business_entity_mail_message_id_seq', 1, false);


--
-- Name: chart_of_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.chart_of_accounts_id_seq', 1, false);


--
-- Name: contact_lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.contact_lists_id_seq', 1, false);


--
-- Name: depreciation_schedules_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.depreciation_schedules_id_seq', 1, false);


--
-- Name: documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.documents_id_seq', 1, false);


--
-- Name: email_drafts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.email_drafts_id_seq', 1, false);


--
-- Name: email_templates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.email_templates_id_seq', 1, false);


--
-- Name: emails_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.emails_id_seq', 1, false);


--
-- Name: entity_person_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.entity_person_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: invoice_lines_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.invoice_lines_id_seq', 1, false);


--
-- Name: invoices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.invoices_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: journal_entries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.journal_entries_id_seq', 1, false);


--
-- Name: journal_lines_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.journal_lines_id_seq', 1, false);


--
-- Name: leases_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.leases_id_seq', 1, false);


--
-- Name: mail_attachments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mail_attachments_id_seq', 1, false);


--
-- Name: mail_labels_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mail_labels_id_seq', 1, false);


--
-- Name: mail_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.mail_messages_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 60, true);


--
-- Name: notes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notes_id_seq', 1, false);


--
-- Name: persons_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.persons_id_seq', 1, false);


--
-- Name: reminders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reminders_id_seq', 1, false);


--
-- Name: tenants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tenants_id_seq', 1, false);


--
-- Name: tracking_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tracking_categories_id_seq', 1, false);


--
-- Name: tracking_sub_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tracking_sub_categories_id_seq', 1, false);


--
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transactions_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, false);


--
-- Name: asset_documents asset_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents
    ADD CONSTRAINT asset_documents_pkey PRIMARY KEY (id);


--
-- Name: asset_mail_message asset_mail_message_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_mail_message
    ADD CONSTRAINT asset_mail_message_pkey PRIMARY KEY (id);


--
-- Name: asset_mail_message asset_mm_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_mail_message
    ADD CONSTRAINT asset_mm_unique UNIQUE (asset_id, mail_message_id);


--
-- Name: assets assets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_pkey PRIMARY KEY (id);


--
-- Name: bank_accounts bank_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_accounts
    ADD CONSTRAINT bank_accounts_pkey PRIMARY KEY (id);


--
-- Name: bank_statement_entries bank_statement_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_statement_entries
    ADD CONSTRAINT bank_statement_entries_pkey PRIMARY KEY (id);


--
-- Name: business_entity_mail_message be_mm_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entity_mail_message
    ADD CONSTRAINT be_mm_unique UNIQUE (business_entity_id, mail_message_id);


--
-- Name: business_entities business_entities_abn_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities
    ADD CONSTRAINT business_entities_abn_unique UNIQUE (abn);


--
-- Name: business_entities business_entities_acn_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities
    ADD CONSTRAINT business_entities_acn_unique UNIQUE (acn);


--
-- Name: business_entities business_entities_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities
    ADD CONSTRAINT business_entities_pkey PRIMARY KEY (id);


--
-- Name: business_entity_mail_message business_entity_mail_message_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entity_mail_message
    ADD CONSTRAINT business_entity_mail_message_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: chart_of_accounts chart_of_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_pkey PRIMARY KEY (id);


--
-- Name: chart_of_accounts coa_entity_code_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT coa_entity_code_unique UNIQUE (business_entity_id, account_code);


--
-- Name: contact_lists contact_lists_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contact_lists
    ADD CONSTRAINT contact_lists_pkey PRIMARY KEY (id);


--
-- Name: depreciation_schedules depreciation_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.depreciation_schedules
    ADD CONSTRAINT depreciation_schedules_pkey PRIMARY KEY (id);


--
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_pkey PRIMARY KEY (id);


--
-- Name: email_drafts email_drafts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_drafts
    ADD CONSTRAINT email_drafts_pkey PRIMARY KEY (id);


--
-- Name: email_templates email_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_pkey PRIMARY KEY (id);


--
-- Name: emails emails_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.emails
    ADD CONSTRAINT emails_pkey PRIMARY KEY (id);


--
-- Name: entity_person entity_person_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entity_person
    ADD CONSTRAINT entity_person_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: invoices invoice_entity_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoice_entity_number_unique UNIQUE (business_entity_id, invoice_number);


--
-- Name: invoice_lines invoice_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.invoice_lines
    ADD CONSTRAINT invoice_lines_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: journal_entries journal_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_pkey PRIMARY KEY (id);


--
-- Name: journal_entries journal_entries_reference_number_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_reference_number_unique UNIQUE (reference_number);


--
-- Name: journal_lines journal_lines_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_pkey PRIMARY KEY (id);


--
-- Name: leases leases_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leases
    ADD CONSTRAINT leases_pkey PRIMARY KEY (id);


--
-- Name: mail_attachments mail_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_attachments
    ADD CONSTRAINT mail_attachments_pkey PRIMARY KEY (id);


--
-- Name: mail_label_mail_message mail_label_mail_message_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_label_mail_message
    ADD CONSTRAINT mail_label_mail_message_pkey PRIMARY KEY (mail_label_id, mail_message_id);


--
-- Name: mail_labels mail_labels_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_labels
    ADD CONSTRAINT mail_labels_pkey PRIMARY KEY (id);


--
-- Name: mail_messages mail_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_messages
    ADD CONSTRAINT mail_messages_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notes notes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: persons persons_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.persons
    ADD CONSTRAINT persons_pkey PRIMARY KEY (id);


--
-- Name: reminders reminders_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reminders
    ADD CONSTRAINT reminders_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tenants tenants_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_pkey PRIMARY KEY (id);


--
-- Name: tracking_categories tracking_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tracking_categories
    ADD CONSTRAINT tracking_categories_pkey PRIMARY KEY (id);


--
-- Name: tracking_sub_categories tracking_sub_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tracking_sub_categories
    ADD CONSTRAINT tracking_sub_categories_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: mail_messages uniq_user_gmail_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_messages
    ADD CONSTRAINT uniq_user_gmail_id UNIQUE (user_id, gmail_id);


--
-- Name: mail_messages uniq_user_message_id; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_messages
    ADD CONSTRAINT uniq_user_message_id UNIQUE (user_id, message_id);


--
-- Name: mail_messages uniq_user_source_hash; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mail_messages
    ADD CONSTRAINT uniq_user_source_hash UNIQUE (user_id, source_hash);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: bank_statement_entries_bank_account_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX bank_statement_entries_bank_account_id_index ON public.bank_statement_entries USING btree (bank_account_id);


--
-- Name: chart_of_accounts_business_entity_id_account_category_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX chart_of_accounts_business_entity_id_account_category_index ON public.chart_of_accounts USING btree (business_entity_id, account_category);


--
-- Name: chart_of_accounts_business_entity_id_account_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX chart_of_accounts_business_entity_id_account_type_index ON public.chart_of_accounts USING btree (business_entity_id, account_type);


--
-- Name: contact_lists_business_entity_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX contact_lists_business_entity_id_index ON public.contact_lists USING btree (business_entity_id);


--
-- Name: depreciation_schedules_asset_id_financial_year_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX depreciation_schedules_asset_id_financial_year_index ON public.depreciation_schedules USING btree (asset_id, financial_year);


--
-- Name: email_drafts_business_entity_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX email_drafts_business_entity_id_index ON public.email_drafts USING btree (business_entity_id);


--
-- Name: email_drafts_scheduled_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX email_drafts_scheduled_at_index ON public.email_drafts USING btree (scheduled_at);


--
-- Name: email_drafts_user_id_created_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX email_drafts_user_id_created_at_index ON public.email_drafts USING btree (user_id, created_at);


--
-- Name: email_drafts_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX email_drafts_user_id_index ON public.email_drafts USING btree (user_id);


--
-- Name: email_templates_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX email_templates_user_id_index ON public.email_templates USING btree (user_id);


--
-- Name: email_templates_user_id_name_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX email_templates_user_id_name_index ON public.email_templates USING btree (user_id, name);


--
-- Name: invoice_lines_invoice_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX invoice_lines_invoice_id_index ON public.invoice_lines USING btree (invoice_id);


--
-- Name: invoices_business_entity_id_issue_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX invoices_business_entity_id_issue_date_index ON public.invoices USING btree (business_entity_id, issue_date);


--
-- Name: invoices_business_entity_id_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX invoices_business_entity_id_status_index ON public.invoices USING btree (business_entity_id, status);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: journal_entries_business_entity_id_entry_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX journal_entries_business_entity_id_entry_date_index ON public.journal_entries USING btree (business_entity_id, entry_date);


--
-- Name: journal_entries_source_type_source_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX journal_entries_source_type_source_id_index ON public.journal_entries USING btree (source_type, source_id);


--
-- Name: journal_lines_journal_entry_id_chart_of_account_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX journal_lines_journal_entry_id_chart_of_account_id_index ON public.journal_lines USING btree (journal_entry_id, chart_of_account_id);


--
-- Name: journal_lines_tracking_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX journal_lines_tracking_idx ON public.journal_lines USING btree (tracking_category_id, tracking_sub_category_id);


--
-- Name: mail_attachments_mail_message_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_attachments_mail_message_id_index ON public.mail_attachments USING btree (mail_message_id);


--
-- Name: mail_label_mail_message_mail_label_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_label_mail_message_mail_label_id_index ON public.mail_label_mail_message USING btree (mail_label_id);


--
-- Name: mail_label_mail_message_mail_message_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_label_mail_message_mail_message_id_index ON public.mail_label_mail_message USING btree (mail_message_id);


--
-- Name: mail_labels_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_labels_user_id_index ON public.mail_labels USING btree (user_id);


--
-- Name: mail_messages_gmail_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_messages_gmail_id_index ON public.mail_messages USING btree (gmail_id);


--
-- Name: mail_messages_message_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_messages_message_id_index ON public.mail_messages USING btree (message_id);


--
-- Name: mail_messages_sender_email_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_messages_sender_email_index ON public.mail_messages USING btree (sender_email);


--
-- Name: mail_messages_sent_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_messages_sent_date_index ON public.mail_messages USING btree (sent_date);


--
-- Name: mail_messages_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX mail_messages_user_id_index ON public.mail_messages USING btree (user_id);


--
-- Name: reminders_next_reminder_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX reminders_next_reminder_date_index ON public.reminders USING btree (next_reminder_date);


--
-- Name: reminders_reminder_type_reminder_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX reminders_reminder_type_reminder_id_index ON public.reminders USING btree (reminder_type, reminder_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: tracking_categories_business_entity_id_is_active_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX tracking_categories_business_entity_id_is_active_index ON public.tracking_categories USING btree (business_entity_id, is_active);


--
-- Name: tracking_categories_business_entity_id_sort_order_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX tracking_categories_business_entity_id_sort_order_index ON public.tracking_categories USING btree (business_entity_id, sort_order);


--
-- Name: tracking_sub_categories_tracking_category_id_is_active_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX tracking_sub_categories_tracking_category_id_is_active_index ON public.tracking_sub_categories USING btree (tracking_category_id, is_active);


--
-- Name: tracking_sub_categories_tracking_category_id_sort_order_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX tracking_sub_categories_tracking_category_id_sort_order_index ON public.tracking_sub_categories USING btree (tracking_category_id, sort_order);


--
-- Name: transactions_bank_account_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_bank_account_id_index ON public.transactions USING btree (bank_account_id);


--
-- Name: transactions_business_entity_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_business_entity_id_index ON public.transactions USING btree (business_entity_id);


--
-- Name: transactions_related_entity_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_related_entity_id_index ON public.transactions USING btree (related_entity_id);


--
-- Name: transactions_tracking_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX transactions_tracking_idx ON public.transactions USING btree (tracking_category_id, tracking_sub_category_id);


--
-- Name: asset_documents asset_documents_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents
    ADD CONSTRAINT asset_documents_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: asset_mail_message asset_mail_message_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_mail_message
    ADD CONSTRAINT asset_mail_message_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: asset_mail_message asset_mail_message_mail_message_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_mail_message
    ADD CONSTRAINT asset_mail_message_mail_message_id_foreign FOREIGN KEY (mail_message_id) REFERENCES public.mail_messages(id) ON DELETE CASCADE;


--
-- Name: assets assets_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: assets assets_depreciation_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_depreciation_account_id_foreign FOREIGN KEY (depreciation_account_id) REFERENCES public.chart_of_accounts(id) ON DELETE SET NULL;


--
-- Name: assets assets_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: bank_accounts bank_accounts_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_accounts
    ADD CONSTRAINT bank_accounts_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: bank_statement_entries bank_statement_entries_bank_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_statement_entries
    ADD CONSTRAINT bank_statement_entries_bank_account_id_foreign FOREIGN KEY (bank_account_id) REFERENCES public.bank_accounts(id) ON DELETE CASCADE;


--
-- Name: bank_statement_entries bank_statement_entries_transaction_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_statement_entries
    ADD CONSTRAINT bank_statement_entries_transaction_id_foreign FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE SET NULL;


--
-- Name: business_entities business_entities_appointor_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities
    ADD CONSTRAINT business_entities_appointor_entity_id_foreign FOREIGN KEY (appointor_entity_id) REFERENCES public.business_entities(id) ON DELETE SET NULL;


--
-- Name: business_entities business_entities_appointor_person_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities
    ADD CONSTRAINT business_entities_appointor_person_id_foreign FOREIGN KEY (appointor_person_id) REFERENCES public.persons(id) ON DELETE SET NULL;


--
-- Name: business_entities business_entities_person_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities
    ADD CONSTRAINT business_entities_person_id_foreign FOREIGN KEY (person_id) REFERENCES public.persons(id) ON DELETE SET NULL;


--
-- Name: business_entities business_entities_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entities
    ADD CONSTRAINT business_entities_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: business_entity_mail_message business_entity_mail_message_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entity_mail_message
    ADD CONSTRAINT business_entity_mail_message_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: business_entity_mail_message business_entity_mail_message_mail_message_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.business_entity_mail_message
    ADD CONSTRAINT business_entity_mail_message_mail_message_id_foreign FOREIGN KEY (mail_message_id) REFERENCES public.mail_messages(id) ON DELETE CASCADE;


--
-- Name: chart_of_accounts chart_of_accounts_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: chart_of_accounts chart_of_accounts_parent_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chart_of_accounts
    ADD CONSTRAINT chart_of_accounts_parent_account_id_foreign FOREIGN KEY (parent_account_id) REFERENCES public.chart_of_accounts(id) ON DELETE SET NULL;


--
-- Name: contact_lists contact_lists_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contact_lists
    ADD CONSTRAINT contact_lists_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: depreciation_schedules depreciation_schedules_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.depreciation_schedules
    ADD CONSTRAINT depreciation_schedules_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: documents documents_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: documents documents_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: documents documents_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: email_drafts email_drafts_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_drafts
    ADD CONSTRAINT email_drafts_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE SET NULL;


--
-- Name: email_drafts email_drafts_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_drafts
    ADD CONSTRAINT email_drafts_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.email_templates(id) ON DELETE SET NULL;


--
-- Name: email_drafts email_drafts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_drafts
    ADD CONSTRAINT email_drafts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: email_templates email_templates_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: entity_person entity_person_appointor_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entity_person
    ADD CONSTRAINT entity_person_appointor_entity_id_foreign FOREIGN KEY (appointor_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: entity_person entity_person_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entity_person
    ADD CONSTRAINT entity_person_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: entity_person entity_person_entity_trustee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entity_person
    ADD CONSTRAINT entity_person_entity_trustee_id_foreign FOREIGN KEY (entity_trustee_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: entity_person entity_person_person_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entity_person
    ADD CONSTRAINT entity_person_person_id_foreign FOREIGN KEY (person_id) REFERENCES public.persons(id) ON DELETE CASCADE;


--
-- Name: invoice_lines invoice_lines_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.invoice_lines
    ADD CONSTRAINT invoice_lines_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: journal_entries journal_entries_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: journal_entries journal_entries_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_entries
    ADD CONSTRAINT journal_entries_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: journal_lines journal_lines_chart_of_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_chart_of_account_id_foreign FOREIGN KEY (chart_of_account_id) REFERENCES public.chart_of_accounts(id) ON DELETE CASCADE;


--
-- Name: journal_lines journal_lines_journal_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_journal_entry_id_foreign FOREIGN KEY (journal_entry_id) REFERENCES public.journal_entries(id) ON DELETE CASCADE;


--
-- Name: journal_lines journal_lines_tracking_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_tracking_category_id_foreign FOREIGN KEY (tracking_category_id) REFERENCES public.tracking_categories(id) ON DELETE SET NULL;


--
-- Name: journal_lines journal_lines_tracking_sub_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.journal_lines
    ADD CONSTRAINT journal_lines_tracking_sub_category_id_foreign FOREIGN KEY (tracking_sub_category_id) REFERENCES public.tracking_sub_categories(id) ON DELETE SET NULL;


--
-- Name: leases leases_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leases
    ADD CONSTRAINT leases_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: leases leases_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.leases
    ADD CONSTRAINT leases_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE SET NULL;


--
-- Name: notes notes_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: notes notes_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: notes notes_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: reminders reminders_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reminders
    ADD CONSTRAINT reminders_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: reminders reminders_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reminders
    ADD CONSTRAINT reminders_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: reminders reminders_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reminders
    ADD CONSTRAINT reminders_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tenants tenants_asset_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_asset_id_foreign FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- Name: tracking_categories tracking_categories_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tracking_categories
    ADD CONSTRAINT tracking_categories_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE CASCADE;


--
-- Name: tracking_sub_categories tracking_sub_categories_tracking_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tracking_sub_categories
    ADD CONSTRAINT tracking_sub_categories_tracking_category_id_foreign FOREIGN KEY (tracking_category_id) REFERENCES public.tracking_categories(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_bank_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_bank_account_id_foreign FOREIGN KEY (bank_account_id) REFERENCES public.bank_accounts(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_business_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_business_entity_id_foreign FOREIGN KEY (business_entity_id) REFERENCES public.business_entities(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_related_entity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_related_entity_id_foreign FOREIGN KEY (related_entity_id) REFERENCES public.business_entities(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_tracking_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_tracking_category_id_foreign FOREIGN KEY (tracking_category_id) REFERENCES public.tracking_categories(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_tracking_sub_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_tracking_sub_category_id_foreign FOREIGN KEY (tracking_sub_category_id) REFERENCES public.tracking_sub_categories(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict 0HskOECJU7C9KUc1Gdq0de0RLC6loibN8NHv5M1X6F7xW5CqxzyxI9iKL46EXPN

