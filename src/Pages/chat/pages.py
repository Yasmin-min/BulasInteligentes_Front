import streamlit as st
from utils.st import rename
from typing import List, Dict, Any

# ---------- IA (mock) ----------
def responder_ia(mensagens: List[Dict[str, str]]) -> str:
    ultimo_usuario = next((m["content"] for m in reversed(mensagens) if m["role"] == "user"), "")
    return f"Recebi sua mensagem: “{ultimo_usuario}”. Posso analisar a imagem e responder dúvidas gerais."

def _push(role: str, content: str):
    st.session_state.chat_msgs.append({"role": role, "content": content})

@rename("Chat")
def chat():
    st.title("Chat com IA")

    # Estado inicial do histórico
    if "chat_msgs" not in st.session_state:
        st.session_state.chat_msgs = [
            {"role": "system", "content": "Você é um assistente para dúvidas sobre medicamentos. Não substitua orientação médica."}
        ]

    # Sidebar
    # with st.sidebar:
    #     if st.button("🧹 Limpar conversa"):
    #         st.session_state.chat_msgs = st.session_state.chat_msgs[:1]
    #     st.caption("Anexe imagens no campo de chat (ícone de clipe).")
    #     st.markdown("---")
    #     st.caption("Aviso: conteúdo informativo — não é aconselhamento médico.")

    # Render histórico (pula a system)
    for m in st.session_state.chat_msgs[1:]:
        with st.chat_message(m["role"]):
            st.markdown(m["content"])

    # ===== ENTRADA DO USUÁRIO =====
    # OBS: Requer Streamlit recente com suporte a arquivo no chat_input
    prompt = st.chat_input(
        "Diga algo e/ou anexe uma imagem",
        accept_file=True,                # ativa o clipe
        file_type=["png", "jpg", "jpeg"] # restringe tipos
    )

    # `prompt` retorna None (se nada) ou um objeto com `text` e `files`
    if prompt:
        texto = getattr(prompt, "text", None) or ""
        files = getattr(prompt, "files", []) or []

        # 1) Mostrar a mensagem do usuário
        with st.chat_message("user"):
            if texto:
                st.markdown(texto)
            if files:
                # Mostra todas as imagens anexadas
                for f in files:
                    st.image(f, use_column_width=True)

        # 2) Atualiza histórico com o texto (e um marcador de anexos)
        user_content = texto if texto else "(imagem anexada)"
        _push("user", user_content)

        # 3) Resposta da IA (integre seu provedor na função responder_ia)
        with st.chat_message("assistant"):
            with st.spinner("Pensando…"):
                resposta = responder_ia(st.session_state.chat_msgs)
                st.markdown(resposta)

        _push("assistant", resposta)

if __name__ == "__main__":
    chat()
