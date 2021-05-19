from django.shortcuts import render

from django.http import HttpResponse
from colony.models import Client, Space, NetAddress

def index(request):
    """View function for colony page of site."""
    #return HttpResponse("Hello, world. You're at the colony index.")
    
    # Generate counts of some of the main objects
    num_clients = Client.objects.all().count()
    
    context = {
        'num_clients': num_clients,
    }
    
    # Render the HTML template index.html with the data in the context variable
    return render(request, 'index.html', context=context)