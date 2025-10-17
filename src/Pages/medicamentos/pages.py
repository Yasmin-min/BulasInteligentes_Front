import streamlit as st
import requests
from utils.st import rename

API_URL = "#url aqui"  # 🔹 Substitua pela URL real da sua API, ex: http://localhost:8000/medicamentos


# ---- Funções auxiliares ----
@st.cache_data(ttl=30)
def _carregar_medicamentos():
    """Busca lista de medicamentos na API (com cache)."""
    try:
        response = requests.get(API_URL, timeout=10)
        response.raise_for_status()
        data = response.json()

        # Garante formato esperado
        if isinstance(data, list):
            return data
        elif isinstance(data, dict) and "items" in data:
            return data["items"]
        else:
            st.warning("Formato inesperado retornado pela API.")
            return []
    except requests.exceptions.RequestException as e:
        st.error(f"Erro ao conectar à API: {e}")
        return []


def _get_meds():
    """Mantém medicamentos em sessão para seleção local."""
    if "MEDICAMENTOS" not in st.session_state:
        st.session_state["MEDICAMENTOS"] = _carregar_medicamentos()
    return st.session_state["MEDICAMENTOS"]


def _filtrar_busca(query: str):
    meds = _get_meds()
    if not query:
        return meds
    q = query.lower().strip()
    return [m for m in meds if q in m.get("nome", "").lower() or q in m.get("indicações", "").lower()]


def _mostrar_detalhes(med):
    st.subheader(med["nome"])
    cols = st.columns([1, 1, 1])
    with cols[0]:
        st.markdown(f"**Fabricante:** {med.get('fabricante', '-')}")
    with cols[1]:
        st.markdown(f"**Indicações:** {med.get('indicações', '-')}")
    with cols[2]:
        st.markdown(f"**Contraindicações:** {med.get('contraindicações', '-')}")

    st.markdown("---")
    st.markdown(f"**Posologia:** {med.get('posologia', '-')}")
    efeitos = med.get("efeitos_colaterais", [])
    if isinstance(efeitos, list):
        st.markdown("**Efeitos colaterais:** " + ", ".join(efeitos))
    else:
        st.markdown(f"**Efeitos colaterais:** {efeitos}")


def _adicionar_medicamento():
    """Formulário para adicionar novo medicamento via API."""
    with st.expander("➕ Adicionar novo medicamento"):
        with st.form("form_novo_medicamento", clear_on_submit=True):
            nome = st.text_input("Nome do medicamento")
            fabricante = st.text_input("Fabricante")
            indicacoes = st.text_area("Indicações")
            posologia = st.text_area("Posologia")
            efeitos = st.text_area("Efeitos colaterais (separe por vírgula)")
            contra = st.text_area("Contraindicações")
            submitted = st.form_submit_button("Salvar medicamento")

            if submitted:
                if not nome:
                    st.error("O nome do medicamento é obrigatório.")
                else:
                    payload = {
                        "nome": nome.strip(),
                        "fabricante": fabricante.strip() or "Desconhecido",
                        "indicações": indicacoes.strip(),
                        "posologia": posologia.strip(),
                        "efeitos_colaterais": [e.strip() for e in efeitos.split(",") if e.strip()],
                        "contraindicações": contra.strip(),
                    }

                    try:
                        r = requests.post(API_URL, json=payload, timeout=10)
                        r.raise_for_status()
                        st.success(f"Medicamento **{nome}** adicionado com sucesso!")
                        _carregar_medicamentos.clear()  # limpa cache
                        st.session_state["MEDICAMENTOS"] = _carregar_medicamentos()
                    except requests.exceptions.RequestException as e:
                        st.error(f"Erro ao salvar medicamento: {e}")


# ---- Página principal ----
@rename("Medicamentos")
def Main():
    st.title("Medicamentos 💊")

    # Seletor e busca
    sel_key = "med_selecionado"
    st.session_state.setdefault(sel_key, None)

    # 🔹 Botão para adicionar novo medicamento
    _adicionar_medicamento()

    # 🔍 Campo de busca
    query = st.text_input("Buscar medicamento (nome/indicação):", placeholder="ex.: dor, febre, amoxi...")
    lista = _filtrar_busca(query)

    # 🧾 Lista de botões
    st.markdown("### Lista de medicamentos")
    cols_per_row = 3
    for i in range(0, len(lista), cols_per_row):
        cols = st.columns(cols_per_row)
        for j, col in enumerate(cols):
            idx = i + j
            if idx < len(lista):
                med = lista[idx]
                with col:
                    if st.button(med["nome"], key=f"btn_{med['nome']}"):
                        st.session_state[sel_key] = med["nome"]

    # 📋 Exibe detalhes do selecionado
    st.markdown("---")
    if st.session_state[sel_key]:
        med = next((m for m in _get_meds() if m["nome"] == st.session_state[sel_key]), None)
        if med:
            _mostrar_detalhes(med)
    else:
        st.info("Selecione um medicamento para ver detalhes.")
