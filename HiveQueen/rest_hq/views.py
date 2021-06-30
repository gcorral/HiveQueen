#from django.shortcuts import render

#from rest_framework import status
#from rest_framework import generics
from rest_framework import viewsets
#from rest_framework.authentication import BasicAuthentication
#from rest_framework.permissions import IsAuthenticated, DjangoModelPermissions
from rest_framework.pagination import PageNumberPagination, LimitOffsetPagination 
from rest_framework.decorators import api_view
from rest_framework.response import Response
from colony.models import Client, Space, NetAddress
from rest_hq.serializers import ClientSerializer, SpaceSerializer, NetAddressSerializer

# Create your views here.



'''
@api_view(['GET', 'POST'])
def client_list(request):
    
    if request.method == 'GET':
        clients = Client.objects.all()
        serializer = ClientSerializer(clients, many=True)
        return Response(serializer.data)
    
    elif request.method == 'POST':
        serializer = ClientSerializer(data=request.data)
        if serializer.is_valid():
            serializer.save()
            return Response(serializer.data, status=status.HTTP_201_CREATED)
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)

@api_view(['GET', 'PUT', 'DELETE']) 
def client_detail(request,pk):
    try:
        client = Client.objects.get(pk=pk)
    except Client.DoesNotExist:
        return Response(status=status.HTTP_404_NOT_FOUND)
    
    if request.method=='GET':
        serializer = ClientSerializer(client)
        
        return Response(serializer.data) 
    elif request.method=='PUT':
        serializer = ClientSerializer(client,data=request.data)
        if serializer.is_valid():
            serializer.save()
            return Response(serializer.data)
        return Response(serializer.errors, status=status.HTTP_400_BAD_REQUEST)
    
    elif request.method=='DELETE':
        client.delete()
        return Response(status=status.HTTP_204_NO_CONTENT)
'''       

class ClientPagination(LimitOffsetPagination):
    page_size=2


class ClientViewSet(viewsets.ModelViewSet):
    queryset=Client.objects.all()
    serializer_class=ClientSerializer
    #pagination_class=PageNumberPagination
    agination_class=ClientPagination
    #authentication_classes=[BasicAuthentication]
    #permission_classes=[IsAuthenticated, DjangoModelPermissions]

@api_view(['POST'])
def clients_by_space(request):
    clients=Client.objects.filter(space=request.data['space'])
    serializer=ClientSerializer(clients, many=True)
    return Response(serializer.data)
        
'''        
class ClientListView(generics.ListCreateAPIView):
    queryset=Client.objects.all()
    serializer_class=ClientSerializer
    
class ClientDetailView(generics.RetrieveUpdateDestroyAPIView):
    queryset=Client.objects.all() 
    serializer_class=ClientSerializer 
'''   

class SpacePagination(LimitOffsetPagination):
    page_size=2


class SpaceViewSet(viewsets.ModelViewSet):
    queryset=Space.objects.all()
    serializer_class=SpaceSerializer
    #pagination_class=PageNumberPagination
    pagination_class=SpacePagination
    #authentication_classes=[BasicAuthentication]
    #permission_classes=[IsAuthenticated, DjangoModelPermissions]

'''    
class SpaceListView(generics.ListCreateAPIView):
    queryset=Space.objects.all()
    serializer_class=SpaceSerializer
    
class SpaceDetailView(generics.RetrieveUpdateDestroyAPIView):
    queryset=Space.objects.all() 
    serializer_class=SpaceSerializer
'''
  
class NetAddressPagination(LimitOffsetPagination):
    page_size=2  
 
    
class NetAddressViewSet(viewsets.ModelViewSet):
    queryset=NetAddress.objects.all()
    serializer_class=NetAddressSerializer
    #pagination_class=PageNumberPagination
    pagination_class=NetAddressPagination
    #authentication_classes=[BasicAuthentication]
    #permission_classes=[IsAuthenticated, DjangoModelPermissions]       
           
'''    
class NetAddressListView(generics.ListCreateAPIView):
    queryset=NetAddress.objects.all()
    serializer_class=NetAddressSerializer
    
class NetAddressDetailView(generics.RetrieveUpdateDestroyAPIView):
    queryset=NetAddress.objects.all() 
    serializer_class=NetAddressSerializer
'''    