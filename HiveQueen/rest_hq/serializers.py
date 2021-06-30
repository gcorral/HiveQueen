'''
Created on 7 jun. 2021

@author: Gregorio Corral
'''

from rest_framework import serializers
from colony.models import Client, Space, NetAddress

class NetAddressSerializer(serializers.ModelSerializer):
    class Meta:
        model = NetAddress
        fields = '__all__'
        

class ClientSerializer(serializers.ModelSerializer):
    addresses = NetAddressSerializer(read_only=True, many=True)
    class Meta:
        model = Client
        fields = '__all__'

        
class SpaceSerializer(serializers.ModelSerializer):
    clients = ClientSerializer(read_only=True, many=True)
    class Meta:
        model = Space
        fields = '__all__'