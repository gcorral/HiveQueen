from django.db import models
from django.urls import reverse

class Space(models.Model):
    """Model representing a spaces in  witch locate clients."""
    name = models.CharField(max_length=200, help_text='4.1B01')
       
    class Meta:
        ordering = ['name']

    def __str__(self):
        """String for representing the Model object."""
        return self.name
 

class Client(models.Model):
    """Model representing a clients to include in the colony."""  
    name = models.CharField(max_length=200, help_text='it001')
    
    domain = models.CharField(max_length=200, help_text='lab.it.uc3m.es')
    
    space = models.ForeignKey(Space, on_delete=models.SET_NULL, null=True)
  
    class Meta:
        ordering = ['name', 'domain']
        
    def __str__(self):
        """String for representing the Model object."""
        return f'{self.name}.{self.domain}'   
    
    def get_absolute_url(self):
        """Returns the url to access a particular client."""
        return reverse('client-detail', args=[str(self.id)])

  
class NetAddress(models.Model):    
    """Model representing a network address of a client."""
    
    client = models.ForeignKey(Client, on_delete=models.RESTRICT, null=True)
    
    #NET_TYPE_ADDR = (
    #    ('IPv4', 'IPv4'),
    #    ('IPv6', 'IPv6'),
    #)
    
    #ip_add = models.GenericIPAddressField(protocol=NET_TYPE_ADDR)
    ip_add = models.GenericIPAddressField()
    
    class Meta:
        ordering = ['ip_add']
    
    def __str__(self):
        """String for representing the Model object."""
        return self.ip_add