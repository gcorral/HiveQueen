'''
Created on 13 may. 2021

@author: user
'''

from django.urls import path

from . import views

urlpatterns = [ 
    path('', views.index, name='index'),
    path('clients/', views.ClientListView.as_view(), name='clients'),
    path('clients/<str:pk>', views.ClientDetailView.as_view(), name='client-detail'),
]

urlpatterns += [
    path('clients/add/', views.add_client, name='add-client'),
]