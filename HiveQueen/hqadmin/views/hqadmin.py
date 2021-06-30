from django.shortcuts import redirect, render
from django.views.generic import TemplateView
from django.views import generic

from hqadmin.models import Groupmng, User

class UserListView(generic.ListView):
    model = User

class SignUpView(TemplateView):
    template_name = 'reg/signup.html'
