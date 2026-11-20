export type UserRole = 'admin' | 'comercial' | 'tecnico' | 'supervisor'
export type CurrencyCode = 'PEN' | 'USD'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  role_label?: string
  phone?: string | null
  is_active: boolean
}

export type DocumentType = 'RUC' | 'DNI' | 'CE' | 'PASAPORTE'

export interface ClientContact {
  id: number
  client_id: number
  full_name: string
  position?: string | null
  email?: string | null
  phone?: string | null
  is_primary: boolean
  created_at?: string
}

export interface ClientSite {
  id: number
  client_id: number
  site_name: string
  address: string
  district_city?: string | null
  reference?: string | null
  created_at?: string
}

export interface Client {
  id: number
  document_type: DocumentType
  document_number: string
  business_name: string
  trade_name?: string | null
  sector?: string | null
  fiscal_address?: string | null
  phone?: string | null
  email?: string | null
  assigned_user_id?: number | null
  is_active: boolean
  assigned_user?: { id: number; name: string } | null
  contacts?: ClientContact[]
  sites?: ClientSite[]
  quotes?: Quote[]
  leads?: Lead[]
  contacts_count?: number
  sites_count?: number
  quotes_count?: number
  leads_count?: number
  created_at?: string
}

export type LeadStage =
  | 'nuevo'
  | 'visita_tecnica'
  | 'cotizado'
  | 'negociacion'
  | 'ganado'
  | 'perdido'

export type ServiceLine =
  | 'cctv'
  | 'cableado_estructurado'
  | 'redes_wifi'
  | 'mantenimiento'
  | 'integral'

export type ActivityType =
  | 'llamada'
  | 'correo'
  | 'reunion'
  | 'visita_tecnica'
  | 'nota'

export interface LeadActivity {
  id: number
  lead_id: number
  user_id: number
  activity_type: ActivityType
  subject: string
  description?: string | null
  scheduled_at?: string | null
  completed_at?: string | null
  created_at: string
  user?: { id: number; name: string } | null
}

export interface Lead {
  id: number
  code: string
  client_id: number
  site_id?: number | null
  assigned_user_id: number
  technician_id?: number | null
  title: string
  service_line: ServiceLine
  stage: LeadStage
  source: string
  estimated_amount: number | string
  expected_close_date?: string | null
  lost_reason?: string | null
  notes?: string | null
  client?: Client | null
  site?: ClientSite | null
  assigned_user?: { id: number; name: string } | null
  technician?: { id: number; name: string } | null
  activities?: LeadActivity[]
  activities_count?: number
  quotes?: Quote[]
  created_at?: string
}

export type CatalogItemType = 'equipo' | 'material' | 'servicio' | 'mantenimiento'
export type MeasurementUnit = 'UND' | 'MTR' | 'PTO' | 'CJA' | 'GLB' | 'HRS'

export interface CatalogCategory {
  id: number
  name: string
  slug: string
  description?: string | null
  items_count?: number
}

export interface CatalogItem {
  id: number
  category_id: number
  sku: string
  name: string
  description?: string | null
  item_type: CatalogItemType
  unit: MeasurementUnit
  unit_cost: number | string
  sale_price: number | string
  tax_rate: number | string
  is_active: boolean
  category?: { id: number; name: string; slug: string } | null
}

export type QuoteStatus =
  | 'borrador'
  | 'pendiente_aprobacion'
  | 'enviada'
  | 'aprobada'
  | 'rechazada'
  | 'vencida'
export interface CompanyProfile {
  id: number
  name: string
  ruc: string
  address?: string | null
  phone?: string | null
  email?: string | null
  bank_accounts?: string | null
  is_default: boolean
  is_active: boolean
}

export interface ProjectTask {
  id: number
  project_id: number
  section_name: string
  title: string
  assigned_to?: number | null
  is_completed: boolean
  completed_at?: string | null
  assignee?: { id: number; name: string } | null
}

export interface ProjectMaterial {
  id: number
  project_id: number
  catalog_item_id?: number | null
  description: string
  unit: string
  planned_qty: number | string
  used_qty: number | string
  unit_cost: number | string
  serial_numbers?: string | null
}

export interface Project {
  id: number
  code: string
  quote_id: number
  client_id: number
  site_id?: number | null
  company_profile_id?: number | null
  lead_technician_id?: number | null
  title: string
  status: 'planificado' | 'en_ejecucion' | 'pausado' | 'completado'
  progress_percent: number
  start_date?: string | null
  end_date?: string | null
  conformity_notes?: string | null
  client?: Client | null
  site?: ClientSite | null
  quote?: Quote | null
  lead_technician?: { id: number; name: string } | null
  tasks?: ProjectTask[]
  materials?: ProjectMaterial[]
  tasks_count?: number
}

export interface Warehouse {
  id: number
  name: string
  location?: string | null
}

export interface WarehouseStock {
  id: number
  warehouse_id: number
  catalog_item_id: number
  quantity: number | string
  reserved_quantity: number | string
  min_stock: number | string
  warehouse?: Warehouse
  catalog_item?: CatalogItem
}

export interface Supplier {
  id: number
  ruc: string
  business_name: string
  contact_name?: string | null
  email?: string | null
  phone?: string | null
}

export interface PurchaseOrder {
  id: number
  po_number: string
  supplier_id: number
  warehouse_id: number
  status: 'emitida' | 'recibida'
  expected_date?: string | null
  received_at?: string | null
  total: number | string
  supplier?: Supplier
  warehouse?: Warehouse
}

export interface MaintenanceContract {
  id: number
  contract_number: string
  client_id: number
  site_id?: number | null
  title: string
  frequency: string
  monthly_fee: number | string
  currency: CurrencyCode
  sla_hours: number
  start_date: string
  end_date: string
  next_visit_date?: string | null
  status: string
  tickets_count?: number
  client?: Client
  site?: ClientSite
}

export interface SlaTicket {
  id: number
  ticket_number: string
  contract_id?: number | null
  client_id: number
  priority: 'baja' | 'media' | 'alta' | 'critica'
  status: 'abierto' | 'en_proceso' | 'resuelto' | 'cerrado'
  subject: string
  description?: string | null
  due_at?: string | null
  client?: Client
  contract?: MaintenanceContract
  assignee?: { id: number; name: string } | null
}

export interface Payment {
  id: number
  amount: number | string
  payment_method: string
  reference_number?: string | null
  paid_at: string
  recorder?: { id: number; name: string } | null
}

export interface InvoiceMilestone {
  id: number
  quote_id: number
  project_id?: number | null
  client_id: number
  title: string
  percentage: number | string
  amount: number | string
  paid_amount: number | string
  currency: CurrencyCode
  due_date?: string | null
  status: 'pendiente' | 'parcial' | 'pagado' | 'vencido'
  client?: Client
  quote?: Quote
  project?: Project
  payments?: Payment[]
}

export interface QuoteTemplateItem {
  id: number
  quote_template_id: number
  catalog_item_id?: number | null
  section_name: string
  description: string
  unit: MeasurementUnit
  quantity: number | string
  unit_cost: number | string
  unit_price: number | string
}

export interface QuoteTemplate {
  id: number
  name: string
  service_line: ServiceLine
  description?: string | null
  is_active: boolean
  items?: QuoteTemplateItem[]
  items_count?: number
}

export interface SiteSurveyPhoto {
  id: number
  site_survey_id: number
  file_path: string
  caption?: string | null
  url: string
}

export interface SiteSurvey {
  id: number
  lead_id: number
  user_id: number
  cable_meters: number | string
  conduit_type?: string | null
  max_height_meters: number | string
  power_requirements?: string | null
  observations?: string | null
  surveyed_at: string
  user?: { id: number; name: string } | null
  photos?: SiteSurveyPhoto[]
}

export interface QuoteAcceptance {
  id: number
  quote_id: number
  decision: 'aprobada' | 'rechazada'
  signer_name: string
  signer_document?: string | null
  signer_email?: string | null
  signature_data?: string | null
  comment?: string | null
  ip_address?: string | null
  accepted_at: string
}

export interface ActivityLog {
  id: number
  user_id?: number | null
  action: string
  subject_type: string
  subject_id: number
  properties?: Record<string, any> | null
  ip_address?: string | null
  created_at: string
  user?: { id: number; name: string; email: string; role: string } | null
}

export interface QuoteItem {
  id?: number
  quote_id?: number
  catalog_item_id?: number | null
  section_name: string
  description: string
  unit: MeasurementUnit
  quantity: number
  unit_cost: number
  unit_price: number
  discount_percent: number
  line_total?: number
  sort_order?: number
}

export interface QuoteStatusLog {
  id: number
  quote_id: number
  user_id: number
  from_status?: string | null
  to_status: string
  comment?: string | null
  created_at: string
  user?: { id: number; name: string } | null
}

export interface Quote {
  id: number
  quote_number: string
  public_token?: string | null
  version: number
  parent_quote_id?: number | null
  lead_id?: number | null
  client_id: number
  company_profile_id?: number | null
  contact_id?: number | null
  site_id?: number | null
  created_by: number
  approved_by?: number | null
  approved_at?: string | null
  approval_requested_at?: string | null
  approval_notes?: string | null
  status: QuoteStatus
  currency: CurrencyCode
  exchange_rate: number | string
  issue_date: string
  valid_until: string
  subtotal: number | string
  discount_amount: number | string
  tax_amount: number | string
  total: number | string
  payment_terms?: string | null
  delivery_time?: string | null
  warranty_terms?: string | null
  technical_notes?: string | null
  client?: Client | null
  contact?: ClientContact | null
  site?: ClientSite | null
  lead?: Lead | null
  creator?: { id: number; name: string } | null
  approver?: { id: number; name: string } | null
  items?: QuoteItem[]
  items_count?: number
  status_logs?: QuoteStatusLog[]
  latest_acceptance?: QuoteAcceptance | null
  created_at?: string
}

export interface PaginatedResponse<T> {
  current_page: number
  data: T[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

export interface DashboardMetrics {
  kpis: {
    monthly_quoted_total: number
    monthly_approved_total: number
    lead_conversion_rate: number
    active_clients: number
    total_leads: number
    won_leads: number
  }
  pipeline_by_stage: Array<{
    stage: LeadStage
    label: string
    count: number
    total_amount: number
  }>
  recent_quotes: Array<{
    id: number
    quote_number: string
    version: number
    status: QuoteStatus
    status_label: string
    currency: CurrencyCode
    total: number
    issue_date: string | null
    client: { id: number; business_name: string } | null
    creator: { id: number; name: string } | null
  }>
}
