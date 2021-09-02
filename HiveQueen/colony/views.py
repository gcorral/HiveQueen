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
    num_addresses = NetAddress.objects.all().count()
    num_spaces = Space.objects.all().count()
    
    
    context = {
        'num_clients': num_clients,
        'num_spaces': num_spaces,
        'num_addresses': num_addresses,
    }
    
    # Render the HTML template index.html with the data in the context variable
    #return render(request, 'index.html', context=context)
    return render(request, 'material-dashboard-django/index.html', context=context)

#@login_required
class ClientListView(generic.ListView):
    model = Client


#@login_required
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
            client.domain = form.cleaned_data['domain']
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
 
 
#@login_required
#@permission_required('colony.client.can_change_client', raise_exception=True)
class ClientUpdate(UpdateView):
    model = Client
    fields = ['name', 'domain', 'space']
    success_url = reverse_lazy('clients')
    
    
#@login_required
#@permission_required('colony.client.can_delete_client', raise_exception=True)
class ClientDelete(DeleteView):
    model = Client
    success_url = reverse_lazy('clients')  
           
#@login_required 
class SpaceListView(generic.ListView):
    model = Space

#@login_required
class SpaceDetailView(generic.DetailView):
    model = Space 

    
#@login_required
#@permission_required('colony.space.can_add_space', raise_exception=True)          
class SpaceCreate(CreateView):
    model = Space
    fields = ['name']
    #initial = {'date_of_death': '11/06/2020'}


#@login_required
#@permission_required('colony.space.can_change_space', raise_exception=True)
class SpaceUpdate(UpdateView):
    model = Space
    fields = ['name']

#@login_required
#@permission_required('colony.space.can_delete_space', raise_exception=True)
class SpaceDelete(DeleteView):
    model = Space
    success_url = reverse_lazy('spaces')  


#@login_required     
class NetAddressListView(generic.ListView):
    model = NetAddress


@login_required
def netaddress_list(request):
    """View function for list netaddresses."""
    
    context = {
    }
    
    # Render the HTML template index.html with the data in the context variable
    return render(request, 'netaddress_list.html', context=context)
    
#@login_required 
class NetAddressDetailView(generic.DetailView):
    model = NetAddress 
 
 
@login_required
def netaddress_detail(request, pk):
    """View function for netaddress detail.""" 
    
    context = {
    }
 
    # Render the HTML template index.html with the data in the context variable
    return render(request, 'colony/netaddress_detail.html', context=context)
 
          
#@login_required
#@permission_required('colony.netaddress.can_add_netaddress', raise_exception=True)          
class NetAddressCreate(CreateView):
    model = NetAddress
    fields = ['ip_add']
    #initial = {'date_of_death': '11/06/2020'}

#@login_required
#@permission_required('colony.netaddress.can_change_netaddress', raise_exception=True)
class NetAddressUpdate(UpdateView):
    model = NetAddress
    fields = ['client', 'ip_add']

#@login_required
#@permission_required('colony.netaddress.can_delete_netaddress', raise_exception=True)
class NetAddressDelete(DeleteView):
    model = NetAddress
    success_url = reverse_lazy('netaddresses')    