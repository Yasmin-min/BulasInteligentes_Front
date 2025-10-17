import streamlit as st
from utils.st import rename

@rename('Home')
def home():
    st.title("💊 Bulas Inteligentes")
    st.subheader("Simplificando medicamentos com Inteligência Artificial")

    st.markdown("---")
    st.markdown(
        """
        ### 🧠 Sobre o Projeto
        O **Bulas Inteligentes** é um aplicativo web desenvolvido para **simplificar o entendimento das bulas de medicamentos**.
        Ele utiliza **Modelos de Linguagem (LLMs)** — como o ChatGPT — para transformar textos técnicos em explicações claras,
        acessíveis e seguras para qualquer pessoa.

        Muitas vezes, as bulas são extensas e cheias de termos médicos difíceis.  
        O objetivo do projeto é **traduzir essas informações para uma linguagem simples**, ajudando o usuário a usar seus medicamentos
        de forma **mais segura e consciente**.
        """
    )

    st.markdown("---")
    st.markdown(
        """
        ### 🎯 Objetivo Geral
        Utilizar **inteligência artificial** para **tornar as informações das bulas mais compreensíveis e acessíveis**, 
        promovendo segurança e entendimento no uso dos medicamentos — tanto em tratamentos simples quanto complexos.
        """
    )

    st.markdown("---")
    st.markdown(
        """
        ### ⚙️ Objetivos Específicos
        - Desenvolver uma **interface web amigável**, feita com Streamlit.  
        - Integrar uma **API com o ChatGPT** para resumir bulas de medicamentos.  
        - Permitir **buscas por nome do medicamento**.  
        - Armazenar os dados e interações dos usuários de forma **segura em banco de dados relacional**.  
        - Validar a clareza dos resumos, garantindo que sejam compreensíveis para todos.  
        """
    )

    st.markdown("---")
    st.markdown(
        """
        ### 🧩 Metodologia
        O desenvolvimento segue etapas de:
        1. **Levantamento de requisitos** – Identificação do público-alvo e das informações essenciais das bulas.  
        2. **Desenvolvimento do sistema** – Criação da interface e integração com o ChatGPT.  
        3. **Análise e refinamento** – Avaliação da clareza e eficácia dos resumos gerados.  
        """
    )

    st.markdown("---")
    st.markdown(
        """
        ### 👥 Sociedade Impactada
        O público-alvo são **usuários comuns**, sem formação em saúde, que buscam compreender melhor as informações
        sobre os medicamentos que utilizam.  
        Com uma linguagem simplificada e direta, o projeto contribui para decisões mais seguras sobre o uso de remédios
        e reduz o risco de automedicação inadequada.
        """
    )

    st.markdown("---")
    st.markdown(
        """
        ### 🚀 Resultados Esperados
        - Facilitar a compreensão das informações das bulas.  
        - Aumentar a segurança no uso de medicamentos.  
        - Estimular decisões mais conscientes sobre saúde.  
        - Aproximar a linguagem médica do entendimento comum.  
        - Incentivar o uso responsável de tecnologias de **IA aplicada à saúde**.  
        """
    )

    st.markdown("---")
    st.markdown(
        """
        ### 🩺 Conclusão
        O **Bulas Inteligentes** nasce da necessidade de unir **tecnologia e acessibilidade** na área da saúde.  
        Ao simplificar informações técnicas e promover o entendimento das bulas, o projeto busca 
        **empoderar o usuário** e **melhorar a segurança** no uso de medicamentos — transformando a forma como as pessoas interagem com as informações farmacêuticas.
        """
    )

    st.info("Este projeto foi desenvolvido como parte do **Projeto Integrador Computação IV** no Centro Universitário Espírito-santense (UNESC), sob orientação do professor Howard Cruz Roatti.")
