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
%%{init: {'layout': 'dagre'}}%%
erDiagram

    users {
        id string PK
        name string
        email string
        email_verified_at datetime
        password string
        remember_token string
        phone string
        avatar_path string
        preferences json
        last_assistant_interaction_at datetime
        created_at datetime
        updated_at datetime
    }

    password_reset_tokens {
        email string PK
        token string
        created_at datetime
    }

    sessions {
        id string PK
        user_id string
        ip_address string
        user_agent string
        payload text
        last_activity int
    }

    personal_access_tokens {
        id int PK
        tokenable_type string
        tokenable_id string
        name string
        token string
        abilities text
        last_used_at datetime
        expires_at datetime
        created_at datetime
        updated_at datetime
    }

    cache {
        key string PK
        value text
        expiration int
    }

    cache_locks {
        key string PK
        owner string
        expiration int
    }

    jobs {
        id int PK
        queue string
        payload text
        attempts int
        reserved_at datetime
        available_at datetime
        created_at datetime
    }

    job_batches {
        id string PK
        name string
        total_jobs int
        pending_jobs int
        failed_jobs int
        failed_job_ids text
        options text
        cancelled_at datetime
        created_at datetime
        finished_at datetime
    }

    failed_jobs {
        id int PK
        uuid string
        connection string
        queue string
        payload text
        exception text
        failed_at datetime
    }

    medications {
        id int PK
        name string
        slug string
        human_summary text
        posology text
        indications text
        contraindications text
        interaction_alerts text
        composition json
        half_life_notes text
        storage_guidance text
        disclaimer text
        sources json
        source string
        fetched_at datetime
        raw_payload json
        created_at datetime
        updated_at datetime
    }

    medication_queries {
        id int PK
        user_id int
        medication_id int
        query text
        normalized_query text
        status string
        from_cache boolean
        completion_tokens int
        prompt_tokens int
        total_tokens int
        latency_ms int
        created_at datetime
        updated_at datetime
    }

    missing_medications {
        id int PK
        name string
        slug string
        occurrences int
        notes text
        last_requested_at datetime
        context json
        created_at datetime
        updated_at datetime
    }

    user_allergies {
        id int PK
        user_id int
        allergen string
        allergen_slug string
        reaction string
        severity string
        notes text
        metadata json
        created_at datetime
        updated_at datetime
    }

    user_medication_courses {
        id int PK
        user_id int
        medication_id int
        medication_name string
        dosage string
        route string
        frequency string
        interval_minutes int
        start_at datetime
        end_at datetime
        is_active boolean
        prescribed_by string
        diagnosis string
        notes text
        metadata json
        created_at datetime
        updated_at datetime
    }

    treatment_plans {
        id int PK
        user_id int
        title string
        status string
        instructions text
        start_at datetime
        end_at datetime
        source string
        is_active boolean
        metadata json
        created_at datetime
        updated_at datetime
    }

    treatment_plan_items {
        id int PK
        treatment_plan_id int
        medication_id int
        medication_name string
        dosage string
        route string
        instructions text
        interval_minutes int
        total_doses int
        duration_days int
        first_dose_at datetime
        last_calculated_at datetime
        metadata json
        created_at datetime
        updated_at datetime
    }

    treatment_plan_schedules {
        id int PK
        treatment_plan_item_id int
        scheduled_at datetime
        taken_at datetime
        status string
        deviation_minutes int
        was_skipped boolean
        notes text
        metadata json
        created_at datetime
        updated_at datetime
    }

    prescription_uploads {
        id int PK
        user_id int
        original_name string
        file_path string
        status string
        extracted_text text
        parsed_payload json
        failure_reason text
        processed_at datetime
        created_at datetime
        updated_at datetime
    }

    assistant_messages {
        id int PK
        user_id int
        message_uuid string
        role string
        content text
        metadata json
        prompt_tokens int
        completion_tokens int
        total_tokens int
        created_at datetime
        updated_at datetime
    }

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
