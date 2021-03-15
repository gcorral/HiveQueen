'''
Created on 15 mar. 2021

@author: user
'''

from rest_framework import serializers
from HiveQueenRest.models import Clients

class HiveQueenRestSerializer(serializers.ModelSerializer):
    '''
    classdocs
    '''

    class Meta:
        model = Clients
        fields = ['id', 'name', 'ipv4', 'lab' ]

      
    #def __init__(self, params):
    #    '''
    #    Constructor
    #    '''
    # */   