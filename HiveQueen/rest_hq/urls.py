'''
Created on 16 mar. 2021

@author: user
'''

from django.urls import path, include
from rest_hq import views
from rest_framework.routers import DefaultRouter

client_router = DefaultRouter()
client_router.register('clients', views.ClientViewSet)

space_router = DefaultRouter()
space_router.register('spaces', views.SpaceViewSet)

netaddress_router = DefaultRouter()
netaddress_router.register('netaddresses', views.NetAddressViewSet)

urlpatterns = [
    #path('clients/', views.ClientListView.as_view()), 
    #path('client/<int:pk>', views.ClientDetailView.as_view()),
    path('', include(client_router.urls)),
    path('clientsbyspace/', views.clients_by_space),
    #path('spaces/', views.SpaceListView.as_view()), 
    #path('space/<int:pk>', views.SpaceDetailView.as_view()),
    path('', include(space_router.urls)),
    #path('addresses/', views.NetAddressListView.as_view()), 
    #path('address/<int:pk>', views.NetAddressDetailView.as_view()),
    path('', include(netaddress_router.urls)),  
]