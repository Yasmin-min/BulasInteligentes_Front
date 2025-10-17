import streamlit as st 
from Pages.medicamentos.pages import Main
from Pages.chat.pages import chat
from Pages.home.pages import home
from utils.st import rename

pages = {'' : [st.Page(home)], 
    'Medicamentos' : [st.Page(Main)],
    'Chat' : [st.Page(chat)]}
st.navigation(pages, position="top").run()