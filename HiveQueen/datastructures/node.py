'''
Created on 13 abr. 2021

@author: gregorio.corral@uc3m.es
'''

class Node():
    '''
    classdocs
    '''
        
    def __init__(self, info=None):
        self.__info = info
        self.__next = None    
        
    def setInfo(self, info):
        self.__info = info
        
    def getInfo(self):
        return self.__info   
    
    def setNext(self, next):
        self.__next = next
        
    def getNext(self):
        return self.__next     
        
    def __str__(self):
        return str(self.__info)