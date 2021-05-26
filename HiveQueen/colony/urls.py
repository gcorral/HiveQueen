'''
Created on 13 may. 2021

@author: user
'''

from django.urls import path

from . import views

urlpatterns = [ 
    path('', views.index, name='index'),   
]

urlpatterns += [
    path('clients/', views.ClientListView.as_view(), name='clients'),
    path('clients/add/', views.add_client, name='add-client'),
    path('clients/<str:pk>', views.ClientDetailView.as_view(), name='client-detail'),
    path('clients/<str:pk>/update/', views.ClientUpdate.as_view(), name='client-update'),
    path('clients/<str:pk>/delete/', views.ClientDelete.as_view(), name='client-delete'),
]

urlpatterns += [
    path('spaces/', views.SpaceListView.as_view(), name='spaces'),
    path('spaces/add/', views.SpaceCreate.as_view(), name='add-space'),
    path('spaces/<str:pk>', views.SpaceDetailView.as_view(), name='space-detail'),  
    path('spaces/<str:pk>/update/', views.SpaceUpdate.as_view(), name='space-update'),
    path('spaces/<str:pk>/delete/', views.SpaceDelete.as_view(), name='space-delete'),  
]

urlpatterns += [
    path('netaddresses/', views.NetAddressListView.as_view(), name='netaddresses'),
    path('netaddresses/add/', views.NetAddressCreate.as_view(), name='add-netaddress'),
    path('netaddresses/<str:pk>', views.NetAddressDetailView.as_view(), name='netaddress-detail'),
    path('netaddresses/<str:pk>/update/', views.NetAddressUpdate.as_view(), name='netaddress-update'),
    path('netaddresses/<str:pk>/delete/', views.NetAddressDelete.as_view(), name='netaddress-delete'), 
]