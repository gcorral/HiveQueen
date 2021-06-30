from django.shortcuts import render

from rest_framework import status
from rest_framework.decorators import api_view
from rest_framework.response import Response
from HiveQueenRest.models import Clients
from HiveQueenRest.serializers import HiveQueenRestSerializer

@api_view(['GET', 'POST'])
def clients_list(request):
    """
    List all clients, or create a new Client.
    """
    if request.method == 'GET':
        clients = Clients.objects.all()
        serializer = HiveQueenRestSerializer(clients, many=True)
        return Response(serializer.data)

    elif request.method == 'POST':
        serializer = HiveQueenRestSerializer(data=request.data)
        if serializer.is_valid():
            serializer.save()
            return Response(serializer.data, status=status.HTTP_201_CREATED)
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)
 
 
def client_detail(request,pk):   
    try:
        client = Client.objects.get(pk=pk)
    except Client.DoesNoExist:  
        return Response(status.HTTP_404_NOT_FOUND)  