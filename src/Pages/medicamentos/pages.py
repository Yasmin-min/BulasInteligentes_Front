import streamlit as st
from utils.st import rename

# ---- Exemplo de "banco" em memória ----
MEDICAMENTOS = [
    {
        "nome": "Paracetamol 750mg",
        "fabricante": "ACME Pharma",
        "indicações": "Analgésico e antipirético.",
        "posologia": "Adultos: 500–750 mg a cada 6–8h (máx. 4 g/dia).",
        "efeitos_colaterais": ["Náusea", "Sonolência (raro)"],
        "contraindicações": "Doença hepática grave, hipersensibilidade ao composto."
    },
    {
        "nome": "Ibuprofeno 400mg",
        "fabricante": "HealthLabs",
        "indicações": "Dor leve a moderada, anti-inflamatório.",
        "posologia": "Adultos: 200–400 mg a cada 6–8h (máx. 1,2 g/dia sem orientação médica).",
        "efeitos_colaterais": ["Desconforto gástrico", "Azia"],
        "contraindicações": "Úlcera ativa, insuf. renal grave, 3º tri. gestação."
    },
    {
        "nome": "Amoxicilina 500mg",
        "fabricante": "BioMed",
        "indicações": "Infecções bacterianas sensíveis.",
        "posologia": "Adultos: 500 mg a cada 8h por 7–14 dias (conforme orientação médica).",
        "efeitos_colaterais": ["Diarréia", "Exantema"],
        "contraindicações": "Alergia a penicilinas/β-lactâmicos."
    },
]

def _filtrar_busca(query: str):
    if not query:
        return MEDICAMENTOS
    q = query.lower().strip()
    return [m for m in MEDICAMENTOS if q in m["nome"].lower() or q in m.get("indicações","").lower()]

def _mostrar_detalhes(med):
    st.subheader(med["nome"])
    cols = st.columns([1,1,1])
    with cols[0]:
        st.markdown(f"**Fabricante:** {med['fabricante']}")
    with cols[1]:
        st.markdown(f"**Indicações:** {med['indicações']}")
    with cols[2]:
        st.markdown(f"**Contraindicações:** {med['contraindicações']}")

    st.markdown("---")
    st.markdown(f"**Posologia:** {med['posologia']}")
    st.markdown("**Efeitos colaterais:**")
    st.write(", ".join(med["efeitos_colaterais"]))

@rename('Medicamentos')
def Main():
    st.title("Medicamentos")

    # Estado selecionado
    sel_key = "med_selecionado"
    st.session_state.setdefault(sel_key, None)

    # Busca
    query = st.text_input("Buscar medicamento (nome/indicação):", placeholder="ex.: dor, febre, amoxi...")
    lista = _filtrar_busca(query)

    # Grade de botões
    st.markdown("### Lista")
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

    # Detalhes
    st.markdown("---")
    if st.session_state[sel_key]:
        med = next((m for m in MEDICAMENTOS if m["nome"] == st.session_state[sel_key]), None)
        if med:
            _mostrar_detalhes(med)
    else:
        st.info("Selecione um medicamento para ver detalhes.")
