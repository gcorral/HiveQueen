'''
Created on 19 may. 2021

@author: user
'''

from django import forms
from django.core.exceptions import ValidationError
from django.utils.translation import ugettext_lazy as _
import validators

class AddClientForm(forms.Form):
    name = forms.CharField(help_text="Enter client name.")
    domain = forms.CharField(help_text="Enter domain name.")
   
    
    def clean_domain(self):
        domain = self.cleaned_data['domain']
        
        # Check valid domain name
        if not validators.domain(domain):
            raise ValidationError(_('Invalid domain name'))
        
        return domain