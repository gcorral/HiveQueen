'''
Created on 14 abr. 2021

@author: Gregorio Corral
'''
from datastructures.basiclinkedlist import BasicLinkedList

class SortedLinkedList(BasicLinkedList):
    '''
    classdocs
    '''


    def __init__(self, reverse=False):
        '''
        Constructor
        '''
        super().__init__()
        self.__reverse = reverse
        
        
    def insert(self, info):
        current = self.getFirst()
        index = 0
        
        if not self.__reverse:
            while current != None and info > current.getInfo():
                current = current.getNext()
                index += 1
        else:
            while current != None and info < current.getInfo():
                current = current.getNext()
                index += 1
            
        self.insertAt(info, index)
        
                   
    def remove(self, info):
        index = self.getIndexOf(info)
        
        if( index != -1 ):
            if( index > 0 ):
                previous = self.getByIndex(index-1)
                self.extractAfter(previous)
            else:
                self.extract()    
            