'''
Created on 24 may. 2021

@author: user
'''
from django.urls import include, path
from hqadmin.views import hqadmin, admin, groupmng, viewer

urlpatterns = [
    path('', include('django.contrib.auth.urls')), 
    path('signup/', hqadmin.SignUpView.as_view(), name='signup'),
    path('signup/admin', admin.AdminSignUpView.as_view(), name='admin_signup'),
    path('signup/groupmng/', groupmng.GroupmngSignUpView.as_view(), name='groupmng_signup'),
    path('signup/viewer/', viewer.ViewerSignUpView.as_view(), name='viewer_signup'),
]

urlpatterns += [
    path('users/', hqadmin.UserListView.as_view(), name='users'),
    #path('users/add/', hqadmin.add_client, name='add-client'),
]


#urlpatterns += [
#    path('groups/', hqadmin.views.hdadmin.GroupListView.as_view(), name='groups'),
#]