'''
Created on 24 may. 2021

@author: Gregorio Corral Torres <gregorio.corral@uc3m.es>
'''
from django.test import TestCase

from colony.models import Client

class ClientModelTest(TestCase):
    
    @classmethod
    def setUpTestData(cls):
        # Set up non-modified objects used by all test methods
        Client.objects.create(name='it001', domain='lab.it.uc3m.es')