import json
import requests
import streamlit as st
from utils.st import rename

API_BASE = "#url aqui"  # ex.: http://localhost:8000
GET_URL = f"{API_BASE}/ficha-medica"
PUT_URL = f"{API_BASE}/ficha-medica"

def _auth_headers():
    token = st.session_state.get("token")
    return {"Authorization": f"Bearer {token}"} if token else {}

@st.cache_data(ttl=30)
def _carregar_ficha():
    try:
        r = requests.get(GET_URL, headers=_auth_headers(), timeout=10)
        r.raise_for_status()
        data = r.json() or {}
        # Normaliza estrutura esperada
        return {
            "nome": data.get("nome", ""),
            "alergias": data.get("alergias", []),
            "medicamentos_contraindicados": data.get("medicamentos_contraindicados", []),
            "condicoes_saude": data.get("condicoes_saude", []),
            "observacoes": data.get("observacoes", ""),
        }
    except requests.RequestException as e:
        st.error(f"Erro ao carregar ficha: {e}")
        return {
            "nome": "",
            "alergias": [],
            "medicamentos_contraindicados": [],
            "condicoes_saude": [],
            "observacoes": "",
        }

def _salvar_ficha(payload: dict):
    try:
        r = requests.put(PUT_URL, json=payload, headers=_auth_headers(), timeout=15)
        r.raise_for_status()
        return True, r.json() if r.headers.get("content-type", "").startswith("application/json") else {}
    except requests.RequestException as e:
        return False, {"erro": str(e)}

def _lista_para_editor(itens):
    # Converte ["penicilina","dipirona"] -> [{"valor":"penicilina"},{"valor":"dipirona"}]
    return [{"valor": x} for x in (itens or [])]

def _editor_para_lista(rows):
    # Converte [{"valor":"penicilina"}, {"valor":""}] -> ["penicilina"]
    return [str(r.get("valor")).strip() for r in (rows or []) if str(r.get("valor")).strip()]

@rename("Ficha Medica")
def ficha_medica():
    st.title("🩺 Ficha Médica")
    st.caption("Gerencie suas alergias, medicamentos contraindicados e condições de saúde.")

    # -- carregamento inicial
    if "ficha_medica_draft" not in st.session_state:
        st.session_state.ficha_medica_draft = _carregar_ficha()

    col_a, col_b = st.columns([1, 1])
    with col_a:
        if st.button("🔄 Recarregar da API", use_container_width=True):
            _carregar_ficha.clear()
            st.session_state.ficha_medica_draft = _carregar_ficha()
            st.success("Ficha atualizada a partir da API.")

    with col_b:
        st.download_button(
            "⬇️ Exportar JSON",
            data=json.dumps(st.session_state.ficha_medica_draft, ensure_ascii=False, indent=2),
            file_name="ficha_medica.json",
            mime="application/json",
            use_container_width=True,
        )

    st.markdown("### Visualização atual")
    ficha = st.session_state.ficha_medica_draft
    with st.container(border=True):
        c1, c2 = st.columns([1, 1])
        with c1:
            st.markdown(f"**Nome:** {ficha.get('nome') or '-'}")
            st.markdown("**Alergias:**")
            st.write(", ".join(ficha.get("alergias") or []) or "—")
            st.markdown("**Medicamentos contraindicados:**")
            st.write(", ".join(ficha.get("medicamentos_contraindicados") or []) or "—")
        with c2:
            st.markdown("**Condições de saúde:**")
            st.write(", ".join(ficha.get("condicoes_saude") or []) or "—")
            st.markdown("**Observações:**")
            st.write(ficha.get("observacoes") or "—")

    st.markdown("---")
    st.subheader("✏️ Editar ficha")

    with st.form("form_ficha_medica", clear_on_submit=False):
        nome = st.text_input("Nome completo", value=ficha.get("nome", ""))

        st.markdown("**Alergias**")
        alergias_rows = st.data_editor(
            _lista_para_editor(ficha.get("alergias")),
            key="alergias_editor",
            num_rows="dynamic",
            use_container_width=True,
            column_config={"valor": st.column_config.TextColumn("Alergia")},
        )

        st.markdown("**Medicamentos contraindicados**")
        contra_rows = st.data_editor(
            _lista_para_editor(ficha.get("medicamentos_contraindicados")),
            key="contra_editor",
            num_rows="dynamic",
            use_container_width=True,
            column_config={"valor": st.column_config.TextColumn("Medicamento")},
        )

        st.markdown("**Condições de saúde**")
        cond_rows = st.data_editor(
            _lista_para_editor(ficha.get("condicoes_saude")),
            key="condicoes_editor",
            num_rows="dynamic",
            use_container_width=True,
            column_config={"valor": st.column_config.TextColumn("Condição")},
        )

        observacoes = st.text_area("Observações (opcional)", value=ficha.get("observacoes", ""), height=120)

        enviar = st.form_submit_button("💾 Salvar na API", use_container_width=True)
        if enviar:
            payload = {
                "nome": nome.strip(),
                "alergias": _editor_para_lista(alergias_rows),
                "medicamentos_contraindicados": _editor_para_lista(contra_rows),
                "condicoes_saude": _editor_para_lista(cond_rows),
                "observacoes": observacoes.strip(),
            }

            with st.spinner("Enviando..."):
                ok, resp = _salvar_ficha(payload)

            if ok:
                st.success("Ficha médica salva com sucesso!")
                # Atualiza cache e estado local
                _carregar_ficha.clear()
                st.session_state.ficha_medica_draft = payload
            else:
                st.error(f"Falha ao salvar na API: {resp.get('erro','erro desconhecido')}")

    st.markdown("---")
    with st.expander("Opções avançadas"):
        st.code(
            f"""GET {GET_URL}
PUT {PUT_URL}
Headers: Authorization: Bearer <token>  # (opcional)
Body (PUT):
{{
  "nome": "string",
  "alergias": ["string"],
  "medicamentos_contraindicados": ["string"],
  "condicoes_saude": ["string"],
  "observacoes": "string"
}}""",
            language="http",
        )
