'''
Created on 16 mar. 2021

@author: user
'''
from django.urls import path
from HiveQueenRest import views

urlpatterns = [
    path('clients/', views.clients_list),   
]