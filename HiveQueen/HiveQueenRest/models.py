from django.db import models

class Clients(models.Model):
    name = models.CharField('Name',max_length=100, blank=False)
    ip4 = models.CharField('IPv4', max_length=40,blank=False)
    lab = models.CharField('Laboratory', max_length=100,blank=False)
