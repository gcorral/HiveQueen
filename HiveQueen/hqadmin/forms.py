'''
Created on 24 may. 2021

@author: user
'''
from django import forms
from django.contrib.auth.forms import UserCreationForm
from django.db import transaction

from hqadmin.models import Groupmng, User

class AdminSignUpForm(UserCreationForm):
    class Meta(UserCreationForm.Meta):
        model = User

    @transaction.atomic
    def save(self, commit=True):
        user = super().save(commit=False)
        user.is_admin = True
        if commit:
            user.save()
        return user
    
class GroupmngSignUpForm(UserCreationForm):
    
    #TODO.:
    #interests = forms.ModelMultipleChoiceField(
    #    queryset=Subject.objects.all(),
    #    widget=forms.CheckboxSelectMultiple,
    #    required=True
    #)

    class Meta(UserCreationForm.Meta):
        model = User

    @transaction.atomic
    def save(self):
        user = super().save(commit=False)
        user.is_admin = True
        user.save()
        admin = Groupmng.objects.create(user=user)
        #TODO:.
        #admin.interests.add(*self.cleaned_data.get('interests'))
        return user
    
class ViewerSignUpForm(UserCreationForm):
    class Meta(UserCreationForm.Meta):
        model = User

    @transaction.atomic
    def save(self, commit=True):
        user = super().save(commit=False)
        user.is_viewer = True
        if commit:
            user.save()
        return user
    