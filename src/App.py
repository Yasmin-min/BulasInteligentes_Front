import streamlit as st 
from Pages.medicamentos.pages import Main
from Pages.chat.pages import chat
from Pages.home.pages import home
from Pages.ficha_medica.pages import ficha_medica
from utils.st import rename

pages = {'Home' : [st.Page(home)],
    'Ficha_Medica' : [st.Page(ficha_medica)], 
    'Medicamentos' : [st.Page(Main)],
    'Chat' : [st.Page(chat)]}
st.navigation(pages, position="top").run()