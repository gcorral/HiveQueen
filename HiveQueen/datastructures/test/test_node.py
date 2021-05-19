'''
Created on 6 may. 2021

@author: user
'''

from django.test import TestCase

from datastructures.node import Node

class TestNode(TestCase):
    '''
    classdocs
    '''
        
    def setUp(self):
        self.n1 = Node('A')
        self.n2 = Node('B')
        
    
    def testConstructor(self):
        n = Node('A')
        self.assertEqual( n.getInfo(), 'A')  
        
        
    def testSetInfo(self):
        self.n1.setInfo('C')
        self.assertEqual( self.n1.getInfo(), 'C')  
    
        
    def testSetNext(self):
        self.n1.setNext( self.n2 )
        self.assertEqual( self.n1.getNext(), self.n2)      