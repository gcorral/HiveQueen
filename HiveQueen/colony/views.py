from django.shortcuts import render
from django.contrib.auth.decorators import login_required
from django.http import HttpResponse
from django.http import HttpResponseRedirect
from django.urls import reverse
from colony.models import Client, Space, NetAddress

from colony.forms import AddClientForm

@login_required
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


@login_required
def add_client(request):
    client = Client()
    
    # If this is a POST request then process the Form data
    if request.method == 'POST':
        
        # Create a form and populate it with data from the request (binding):
        form = AddClientForm(request.POST)
        
        # Check if the form is valid:
        if form.is_valid():
            # process the data in form.cleaned_data as required
            client.name = form.cleaned_data['name']
            client.name = form.cleaned_data['domain']
            client.save()
            
            # redirect to a new URL:
            return HttpResponseRedirect(reverse('') )
        
    # If this is a GET (or any other method) create the default form.
    else:
        form = AddClientForm(initial={'domain': 'foo.com'})

    context = {
        'form': form,
        'client': client,
    }

    return render(request, 'colony/add_client.html', context)
        