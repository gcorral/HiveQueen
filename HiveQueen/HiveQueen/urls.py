"""hivequeen URL Configuration

The `urlpatterns` list routes URLs to views. For more information please see:
    https://docs.djangoproject.com/en/3.1/topics/http/urls/
Examples:
Function views
    1. Add an import:  from my_app import views
    2. Add a URL to urlpatterns:  path('', views.home, name='home')
Class-based views
    1. Add an import:  from other_app.views import Home
    2. Add a URL to urlpatterns:  path('', Home.as_view(), name='home')
Including another URLconf
    1. Import the include() function: from django.urls import include, path
    2. Add a URL to urlpatterns:  path('blog/', include('blog.urls'))
"""
from django.contrib import admin
from django.urls import path, include
from django.conf.urls import url
from django.views.generic import RedirectView

from rest_framework_jwt.views import refresh_jwt_token
import django_saml2_auth.views

urlpatterns = [
    path('admin/', admin.site.urls),
      
    #path("", include("authentication.urls")), # Auth routes - login / register
    #path('rest/', include('HiveQueenRest.urls')),
]

urlpatterns += [
    url(r'^jwt_refresh', refresh_jwt_token),
    url(r'^sso/', include('django_saml2_auth.urls')),
    path('hqadmin/', include('hqadmin.urls')),
]


urlpatterns += [
    path('colony/', include('colony.urls')),
]

urlpatterns += [
    path('', RedirectView.as_view(url='colony/', permanent=True)),
]

#Add Django site authentication urls (for login, logout, password management)

urlpatterns += [
    path('accounts/', include('django.contrib.auth.urls')),
]

urlpatterns += [
    path('api/', include('rest_hq.urls')),
]

