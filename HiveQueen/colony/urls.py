'''
Created on 13 may. 2021

@author: user
'''

from django.urls import path

from . import views

urlpatterns = [ 
    path('', views.index, name='index'),
]