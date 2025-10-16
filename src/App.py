import streamlit as st 
from Pages.medicamentos.pages import Main
from Pages.chat.pages import chat
from utils.st import rename

@rename('Home')

def home():
    # logotipo = "src/assets/Logo_icone_com_nome.png"
    # st.logo(logotipo, size="small") 
    st.title("Bula inteligente")



pages = {'' : [st.Page(home)], 
    'Medicamentos' : [st.Page(Main)],
    'Chat' : [st.Page(chat)]}
st.navigation(pages, position="top").run()