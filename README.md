# Bulas Inteligentes

Assistente farmacêutico digital que une Laravel 12, Vue 3 + Tailwind e IA para interpretar prescrições, gerar planos de tratamento, monitorar doses e responder dúvidas de forma segura.

## Sumário

-   Visão geral
-   Principais recursos
-   Requisitos
-   Configuração rápida
-   Variáveis de ambiente
-   Modelo de dados (Mermaid)
-   Fluxos da aplicação
-   Endpoints úteis
-   Scripts de desenvolvimento
-   Suporte e notas

## Visão geral

A aplicação permite:

-   Enviar receitas (imagem/PDF) e usar Google Vision + IA para extrair medicamentos e montar um plano de doses.
-   Criar planos manualmente ou a partir de um resumo simples com ajuda da IA.
-   Registrar e acompanhar próximas doses, reagendar e marcar tomadas/puladas.
-   Conversar com o assistente de IA (token-aware) para dúvidas rápidas ou contexto clínico, sempre reforçando avaliação médica.

## Principais recursos

-   **Receitas digitalizadas**: upload com fallback de OCR; quando Google Vision está ativo, envia direto para a API e gera plano.
-   **Plano via IA**: resumo curto gera horários, intervalos e doses, evitando conflitos entre medicamentos.
-   **Dashboard**: próximos 7 dose/eventos, contadores de planos ativos, alergias e receitas pendentes.
-   **Controle de planos**: editar, arquivar/ativar e remover planos; reagendamento de doses.
-   **Assistente**: aceita perguntas gerais (como funciona) e dúvidas de medicamento, priorizando respostas curtas para economizar tokens.

## Requisitos

-   PHP 8.3+ com Composer
-   Node 18+ e npm
-   Banco configurado (MySQL/PostgreSQL/SQLite)
-   Chave OpenAI configurada
-   Chave Google Vision com billing habilitado para OCR em produção

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

-   `OPENAI_API_KEY` / `OPENAI_DEFAULT_MODEL` / `OPENAI_TEMPERATURE`: configuram o assistente e geração de planos.
-   `GOOGLE_VISION_API_KEY`: chave da API; exige billing ativo.  
    Opcional: `GOOGLE_VISION_ENDPOINT`, `GOOGLE_VISION_FEATURE`, `GOOGLE_VISION_TIMEOUT`.
-   Banco: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

## Diagrama do Banco de Dados

```mermaid
erDiagram

    users {
        string id PK
        string name
        string email UNIQUE
        datetime email_verified_at
        string password
        string remember_token
        string phone
        string avatar_path
        string preferences
        datetime last_assistant_interaction_at
        datetime created_at
        datetime updated_at
    }

    password_reset_tokens {
        string email PK
        string token
        datetime created_at
    }

    sessions {
        string id PK
        string user_id FK
        string ip_address
        string user_agent
        string payload
        int last_activity
    }

    personal_access_tokens {
        int id PK
        string tokenable_type
        string tokenable_id
        string name
        string token UNIQUE
        string abilities
        datetime last_used_at
        datetime expires_at
        datetime created_at
        datetime updated_at
    }

    cache {
        string key PK
        string value
        int expiration
    }

    cache_locks {
        string key PK
        string owner
        int expiration
    }

    jobs {
        int id PK
        string queue
        string payload
        int attempts
        datetime reserved_at
        datetime available_at
        datetime created_at
    }

    job_batches {
        string id PK
        string name
        int total_jobs
        int pending_jobs
        int failed_jobs
        string failed_job_ids
        string options
        datetime cancelled_at
        datetime created_at
        datetime finished_at
    }

    failed_jobs {
        int id PK
        string uuid UNIQUE
        string connection
        string queue
        string payload
        string exception
        datetime failed_at
    }

    medications {
        int id PK
        string name
        string slug UNIQUE
        string human_summary
        string posology
        string indications
        string contraindications
        string interaction_alerts
        string composition
        string half_life_notes
        string storage_guidance
        string disclaimer
        string sources
        string source
        datetime fetched_at
        string raw_payload
        datetime created_at
        datetime updated_at
    }

    medication_queries {
        int id PK
        int user_id FK
        int medication_id FK
        string query
        string normalized_query
        string status
        boolean from_cache
        int completion_tokens
        int prompt_tokens
        int total_tokens
        int latency_ms
        datetime created_at
        datetime updated_at
    }

    missing_medications {
        int id PK
        string name
        string slug UNIQUE
        int occurrences
        string notes
        datetime last_requested_at
        string context
        datetime created_at
        datetime updated_at
    }

    user_allergies {
        int id PK
        int user_id FK
        string allergen
        string allergen_slug
        string reaction
        string severity
        string notes
        string metadata
        datetime created_at
        datetime updated_at
    }

    user_medication_courses {
        int id PK
        int user_id FK
        int medication_id FK
        string medication_name
        string dosage
        string route
        string frequency
        int interval_minutes
        datetime start_at
        datetime end_at
        boolean is_active
        string prescribed_by
        string diagnosis
        string notes
        string metadata
        datetime created_at
        datetime updated_at
    }

    treatment_plans {
        int id PK
        int user_id FK
        string title
        string status
        string instructions
        datetime start_at
        datetime end_at
        string source
        boolean is_active
        string metadata
        datetime created_at
        datetime updated_at
    }

    treatment_plan_items {
        int id PK
        int treatment_plan_id FK
        int medication_id FK
        string medication_name
        string dosage
        string route
        string instructions
        int interval_minutes
        int total_doses
        int duration_days
        datetime first_dose_at
        datetime last_calculated_at
        string metadata
        datetime created_at
        datetime updated_at
    }

    treatment_plan_schedules {
        int id PK
        int treatment_plan_item_id FK
        datetime scheduled_at
        datetime taken_at
        string status
        int deviation_minutes
        boolean was_skipped
        string notes
        string metadata
        datetime created_at
        datetime updated_at
    }

    prescription_uploads {
        int id PK
        int user_id FK
        string original_name
        string file_path
        string status
        string extracted_text
        string parsed_payload
        string failure_reason
        datetime processed_at
        datetime created_at
        datetime updated_at
    }

    assistant_messages {
        int id PK
        int user_id FK
        string message_uuid UNIQUE
        string role
        string content
        string metadata
        int prompt_tokens
        int completion_tokens
        int total_tokens
        datetime created_at
        datetime updated_at
    }

    %% RELATIONSHIPS
    users ||--o{ sessions : has
    users ||--o{ personal_access_tokens : has
    users ||--o{ medication_queries : has
    users ||--o{ user_allergies : has
    users ||--o{ user_medication_courses : has
    users ||--o{ treatment_plans : has
    users ||--o{ prescription_uploads : has
    users ||--o{ assistant_messages : has

    medications ||--o{ medication_queries : has
    medications ||--o{ user_medication_courses : has
    medications ||--o{ treatment_plan_items : has

    treatment_plans ||--o{ treatment_plan_items : has
    treatment_plan_items ||--o{ treatment_plan_schedules : has
```

## Fluxos da aplicação

-   **Receitas** (`/prescriptions`):
    1. Envie imagem/PDF.
    2. Quando status for `parsed` ou `text_extracted`, informe início do tratamento e gere plano.
    3. Após gerar, a receita é removida automaticamente (arquivo + registro). Botão “Remover tentativa” apaga uploads com falha.
-   **Planos** (`/plans`):
    -   Criar manualmente ou com resumo + IA.
    -   Editar título, instruções, datas e status; remover plano.
    -   Ver agenda completa e marcar doses (tomada/pulada/reagendar).
-   **Dashboard** (`/`):
    -   Resumo com doses próximas, planos ativos, alergias e receitas pendentes.
    -   Lista “Próximas doses” usa `next_schedules` ou `schedules`.
-   **Assistente** (`/assistant`):
    -   Perguntas gerais ou sobre medicamentos. Loader de “pensando” visível. Respostas curtas para poupar tokens.

## Endpoints úteis (API)

-   Receitas:
    -   `POST /prescriptions/uploads` (multipart) — enviar receita
    -   `GET /prescriptions/uploads` — listar uploads
    -   `POST /prescriptions/uploads/{id}/plan` — gerar plano da receita (remove o upload)
    -   `DELETE /prescriptions/uploads/{id}` — remover tentativa
-   Planos:
    -   `GET /treatment-plans`, `POST /treatment-plans`
    -   `POST /treatment-plans/ai/suggest` — plano via resumo
    -   `PATCH /treatment-plans/{id}`, `DELETE /treatment-plans/{id}`
    -   `PATCH /treatment-plans/{id}/schedules/{schedule}` — registrar/reagendar dose
-   Assistente: `POST /assistant/query`

## Scripts de desenvolvimento

-   `npm run dev` — Vite em hot reload
-   `npm run build` — build de produção
-   `php artisan serve` — servidor local
-   `php artisan migrate` — migrações

## Suporte e notas

-   Se o dashboard ficar carregando, verifique as chamadas `/treatment-plans`, `/profile/medications`, `/profile/allergies`, `/prescriptions/uploads` e a validade do token (todas exigem autenticação).
-   Para OCR: mensagens de erro “billing” ou “API_KEY_SERVICE_BLOCKED” indicam que a chave Google Vision não tem cobrança/permissão. Ative billing ou troque a chave.
-   Mantenha o `OPENAI_API_KEY` válido; prompts pedem respostas curtas para reduzir custo de tokens.

## Integrantes (6SC1)

-   Dyana
-   Eve
-   Wilton
-   Yasmin Neumann
-   Yasmin Sousa
