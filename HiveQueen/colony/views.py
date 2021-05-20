from django.shortcuts import render
from django.contrib.auth.decorators import login_required, permission_required
from django.views.generic.edit import CreateView, UpdateView, DeleteView
from django.urls import reverse_lazy
from django.http import HttpResponse
from django.http import HttpResponseRedirect
from django.urls import reverse
from django.views import generic
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


class ClientListView(generic.ListView):
    model = Client


class ClientDetailView(generic.DetailView):
    model = Client


@login_required
@permission_required('colony.client.can_add_client', raise_exception=True)
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
            return HttpResponseRedirect(reverse('clients'))
        
    # If this is a GET (or any other method) create the default form.
    else:
        form = AddClientForm(initial={'domain': 'foo.com'})

    context = {
        'form': form,
        'client': client,
    }

    return render(request, 'colony/add_client.html', context)
      
 
class SpaceListView(generic.ListView):
    model = Space


class SpaceDetailView(generic.DetailView):
    model = Space 
    
          
class SpaceCreate(CreateView):
    model = Space
    fields = ['name']
    #initial = {'date_of_death': '11/06/2020'}

class SpaceUpdate(UpdateView):
    model = Space
    fields = ['name']

class SpaceDelete(DeleteView):
    model = Space
    success_url = reverse_lazy('spaces')  

    
class NetAddressListView(generic.ListView):
    model = NetAddress


class NetAddressDetailView(generic.DetailView):
    model = NetAddress 
    
          
class NetAddressCreate(CreateView):
    model = NetAddress
    fields = ['client', 'ip_add']
    #initial = {'date_of_death': '11/06/2020'}


class NetAddressUpdate(UpdateView):
    model = Space
    fields = ['client', 'ip_add']


class NetAddressDelete(DeleteView):
    model = Space
    success_url = reverse_lazy('netaddresses')    