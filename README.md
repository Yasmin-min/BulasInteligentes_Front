# Bulas Inteligentes

Assistente farmacêutico digital que une Laravel 12, Vue 3 + Tailwind e IA para interpretar prescrições, gerar planos de tratamento, monitorar doses e responder dúvidas de forma segura.

## Sumário
- Visão geral
- Principais recursos
- Requisitos
- Configuração rápida
- Variáveis de ambiente
- Modelo de dados (Mermaid)
- Fluxos da aplicação
- Endpoints úteis
- Scripts de desenvolvimento
- Suporte e notas

## Visão geral
A aplicação permite:
- Enviar receitas (imagem/PDF) e usar Google Vision + IA para extrair medicamentos e montar um plano de doses.
- Criar planos manualmente ou a partir de um resumo simples com ajuda da IA.
- Registrar e acompanhar próximas doses, reagendar e marcar tomadas/puladas.
- Conversar com o assistente de IA (token-aware) para dúvidas rápidas ou contexto clínico, sempre reforçando avaliação médica.

## Principais recursos
- **Receitas digitalizadas**: upload com fallback de OCR; quando Google Vision está ativo, envia direto para a API e gera plano.
- **Plano via IA**: resumo curto gera horários, intervalos e doses, evitando conflitos entre medicamentos.
- **Dashboard**: próximos 7 dose/eventos, contadores de planos ativos, alergias e receitas pendentes.
- **Controle de planos**: editar, arquivar/ativar e remover planos; reagendamento de doses.
- **Assistente**: aceita perguntas gerais (como funciona) e dúvidas de medicamento, priorizando respostas curtas para economizar tokens.

## Requisitos
- PHP 8.3+ com Composer
- Node 18+ e npm
- Banco configurado (MySQL/PostgreSQL/SQLite)
- Chave OpenAI configurada
- Chave Google Vision com billing habilitado para OCR em produção

## Configuração rápida
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
npm run build    # ou npm run dev para hot reload
php artisan serve
```

## Variáveis de ambiente
- `OPENAI_API_KEY` / `OPENAI_DEFAULT_MODEL` / `OPENAI_TEMPERATURE`: configuram o assistente e geração de planos.
- `GOOGLE_VISION_API_KEY`: chave da API; exige billing ativo.  
  Opcional: `GOOGLE_VISION_ENDPOINT`, `GOOGLE_VISION_FEATURE`, `GOOGLE_VISION_TIMEOUT`.
- Banco: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

## Modelo de dados (Mermaid)
```mermaid
erDiagram
    USERS {
        BIGINT id PK
        STRING name
        STRING email UNIQUE
        TIMESTAMP email_verified_at
        STRING password
        STRING remember_token
        STRING phone
        STRING avatar_path
        JSON preferences
        TIMESTAMP last_assistant_interaction_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    PASSWORD_RESET_TOKENS {
        STRING email PK
        STRING token
        TIMESTAMP created_at
    }
    SESSIONS {
        STRING id PK
        BIGINT user_id FK
        STRING ip_address
        TEXT user_agent
        LONGTEXT payload
        INTEGER last_activity
    }
    PERSONAL_ACCESS_TOKENS {
        BIGINT id PK
        STRING tokenable_type
        BIGINT tokenable_id
        TEXT name
        STRING token UNIQUE
        TEXT abilities
        TIMESTAMP last_used_at
        TIMESTAMP expires_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    CACHE {
        STRING key PK
        MEDIUMTEXT value
        INTEGER expiration
    }
    CACHE_LOCKS {
        STRING key PK
        STRING owner
        INTEGER expiration
    }
    JOBS {
        BIGINT id PK
        STRING queue
        LONGTEXT payload
        TINYINT attempts
        INTEGER reserved_at
        INTEGER available_at
        INTEGER created_at
    }
    JOB_BATCHES {
        STRING id PK
        STRING name
        INTEGER total_jobs
        INTEGER pending_jobs
        INTEGER failed_jobs
        LONGTEXT failed_job_ids
        MEDIUMTEXT options
        INTEGER cancelled_at
        INTEGER created_at
        INTEGER finished_at
    }
    FAILED_JOBS {
        BIGINT id PK
        STRING uuid UNIQUE
        TEXT connection
        TEXT queue
        LONGTEXT payload
        LONGTEXT exception
        TIMESTAMP failed_at
    }
    MEDICATIONS {
        BIGINT id PK
        STRING name
        STRING slug UNIQUE
        TEXT human_summary
        TEXT posology
        TEXT indications
        TEXT contraindications
        TEXT interaction_alerts
        JSON composition
        TEXT half_life_notes
        TEXT storage_guidance
        TEXT disclaimer
        JSON sources
        STRING source
        TIMESTAMP fetched_at
        JSON raw_payload
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    MEDICATION_QUERIES {
        BIGINT id PK
        BIGINT user_id FK
        BIGINT medication_id FK
        STRING query
        STRING normalized_query
        STRING status
        BOOLEAN from_cache
        INTEGER completion_tokens
        INTEGER prompt_tokens
        INTEGER total_tokens
        INTEGER latency_ms
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    MISSING_MEDICATIONS {
        BIGINT id PK
        STRING name
        STRING slug UNIQUE
        INTEGER occurrences
        TEXT notes
        TIMESTAMP last_requested_at
        JSON context
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    USER_ALLERGIES {
        BIGINT id PK
        BIGINT user_id FK
        STRING allergen
        STRING allergen_slug
        STRING reaction
        STRING severity
        TEXT notes
        JSON metadata
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    USER_MEDICATION_COURSES {
        BIGINT id PK
        BIGINT user_id FK
        BIGINT medication_id FK
        STRING medication_name
        STRING dosage
        STRING route
        STRING frequency
        INTEGER interval_minutes
        TIMESTAMP start_at
        TIMESTAMP end_at
        BOOLEAN is_active
        STRING prescribed_by
        STRING diagnosis
        TEXT notes
        JSON metadata
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    TREATMENT_PLANS {
        BIGINT id PK
        BIGINT user_id FK
        STRING title
        STRING status
        TEXT instructions
        TIMESTAMP start_at
        TIMESTAMP end_at
        STRING source
        BOOLEAN is_active
        JSON metadata
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    TREATMENT_PLAN_ITEMS {
        BIGINT id PK
        BIGINT treatment_plan_id FK
        BIGINT medication_id FK
        STRING medication_name
        STRING dosage
        STRING route
        TEXT instructions
        INTEGER interval_minutes
        INTEGER total_doses
        INTEGER duration_days
        TIMESTAMP first_dose_at
        TIMESTAMP last_calculated_at
        JSON metadata
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    TREATMENT_PLAN_SCHEDULES {
        BIGINT id PK
        BIGINT treatment_plan_item_id FK
        TIMESTAMP scheduled_at
        TIMESTAMP taken_at
        STRING status
        INTEGER deviation_minutes
        BOOLEAN was_skipped
        TEXT notes
        JSON metadata
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    PRESCRIPTION_UPLOADS {
        BIGINT id PK
        BIGINT user_id FK
        STRING original_name
        STRING file_path
        STRING status
        TEXT extracted_text
        JSON parsed_payload
        TEXT failure_reason
        TIMESTAMP processed_at
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    ASSISTANT_MESSAGES {
        BIGINT id PK
        BIGINT user_id FK
        UUID message_uuid UNIQUE
        STRING role
        TEXT content
        JSON metadata
        INTEGER prompt_tokens
        INTEGER completion_tokens
        INTEGER total_tokens
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    USERS ||--o{ USER_ALLERGIES : has
    USERS ||--o{ USER_MEDICATION_COURSES : has
    USERS ||--o{ TREATMENT_PLANS : has
    USERS ||--o{ MEDICATION_QUERIES : has
    USERS ||--o{ PRESCRIPTION_UPLOADS : has
    USERS ||--o{ ASSISTANT_MESSAGES : has
    MEDICATIONS ||--o{ MEDICATION_QUERIES : referenced
    MEDICATIONS ||--o{ USER_MEDICATION_COURSES : referenced
    MEDICATIONS ||--o{ TREATMENT_PLAN_ITEMS : referenced
    TREATMENT_PLANS ||--o{ TREATMENT_PLAN_ITEMS : contains
    TREATMENT_PLAN_ITEMS ||--o{ TREATMENT_PLAN_SCHEDULES : schedules
```

## Fluxos da aplicação
- **Receitas** (`/prescriptions`):
  1) Envie imagem/PDF.  
  2) Quando status for `parsed` ou `text_extracted`, informe início do tratamento e gere plano.  
  3) Após gerar, a receita é removida automaticamente (arquivo + registro). Botão “Remover tentativa” apaga uploads com falha.
- **Planos** (`/plans`):
  - Criar manualmente ou com resumo + IA.  
  - Editar título, instruções, datas e status; remover plano.  
  - Ver agenda completa e marcar doses (tomada/pulada/reagendar).
- **Dashboard** (`/`):
  - Resumo com doses próximas, planos ativos, alergias e receitas pendentes.  
  - Lista “Próximas doses” usa `next_schedules` ou `schedules`.
- **Assistente** (`/assistant`):
  - Perguntas gerais ou sobre medicamentos. Loader de “pensando” visível. Respostas curtas para poupar tokens.

## Endpoints úteis (API)
- Receitas:  
  - `POST /prescriptions/uploads` (multipart) — enviar receita  
  - `GET /prescriptions/uploads` — listar uploads  
  - `POST /prescriptions/uploads/{id}/plan` — gerar plano da receita (remove o upload)  
  - `DELETE /prescriptions/uploads/{id}` — remover tentativa
- Planos:  
  - `GET /treatment-plans`, `POST /treatment-plans`  
  - `POST /treatment-plans/ai/suggest` — plano via resumo  
  - `PATCH /treatment-plans/{id}`, `DELETE /treatment-plans/{id}`  
  - `PATCH /treatment-plans/{id}/schedules/{schedule}` — registrar/reagendar dose
- Assistente: `POST /assistant/query`

## Scripts de desenvolvimento
- `npm run dev` — Vite em hot reload
- `npm run build` — build de produção
- `php artisan serve` — servidor local
- `php artisan migrate` — migrações

## Suporte e notas
- Se o dashboard ficar carregando, verifique as chamadas `/treatment-plans`, `/profile/medications`, `/profile/allergies`, `/prescriptions/uploads` e a validade do token (todas exigem autenticação).
- Para OCR: mensagens de erro “billing” ou “API_KEY_SERVICE_BLOCKED” indicam que a chave Google Vision não tem cobrança/permissão. Ative billing ou troque a chave.
- Mantenha o `OPENAI_API_KEY` válido; prompts pedem respostas curtas para reduzir custo de tokens.

## Integrantes (6SC1)
- Dyana
- Eve
- Wilton
- Yasmin Neumann
- Yasmin Sousa
