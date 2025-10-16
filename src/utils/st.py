import streamlit as st

def rename(name):
    def decorator(f):
        f.__name__ = name
        return f
    return decorator