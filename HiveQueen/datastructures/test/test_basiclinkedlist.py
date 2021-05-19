'''
Created on 6 may. 2021

@author: user
'''

from django.test import TestCase

from datastructures.basiclinkedlist import BasicLinkedList

class TestBasicLinkedList(TestCase):
    '''
    classdocs
    '''
    
    def setUp(self):
        self.bll_empty = BasicLinkedList()
        self.bll = BasicLinkedList()
        self.bll.insert('A')
        self.bll.insert('B')
        self.bll.insert('C')
    
    def testSize(self):
        self.assertEqual( self.bll_empty.size(), 0)
        self.assertEqual( self.bll.size(), 3)
        self.assertEqual( len(self.bll), 3)
        

    def testGetFirst(self):
        node = self.bll.getFirst()
        self.assertEqual( node.getInfo(), 'C')
        
            
    def testGetByIndex(self):
        self.assertEqual( self.bll.getByIndex(1).getInfo(), 'B')       
     
        
    def testIsEmpty(self):
        self.assertEqual( self.bll_empty.isEmpty(), True)
        self.assertEqual( self.bll.isEmpty(), False)
    
     
    def testInsert(self):
        self.bll_empty.insert('A')
        self.assertEqual( self.bll_empty.isEmpty(), False)
    
     
    def testInsertAt(self):
        self.bll.insertAt('D', 1)
        self.assertEqual( self.bll.getByIndex(1).getInfo(), 'D')
    
    
    def testExtract(self):
        self.assertEqual( self.bll_empty.extract(), None) 
        self.assertEqual( self.bll.extract(), 'C')  
 
        
    def testInsertAfter(self):
        node = self.bll.getFirst()
        self.bll.insertAfter('D', node)
        self.assertEqual( self.bll.getByIndex(1).getInfo(), 'D')
     
        
    def testExtractAfter(self):
        node = self.bll.getFirst()
        self.assertEqual( self.bll.extractAfter(node), 'B')
        self.assertEqual( self.bll.getByIndex(1).getInfo(), 'A')
        
        
    def testSearchNodeByInfo(self):
        node = self.bll.searchNodeByInfo('A')
        self.assertEqual( node.getInfo(), 'A')
     
        
    def testGetItem(self):
        node = self.bll[2]       
        self.assertEqual( node.getInfo(), 'A')   
 
        
    def testGetLast(self):
        node = self.bll.getLast()
        self.assertEqual( node.getInfo(), 'A')  
    
        
    def testGetIndexOf(self):
        self.assertEqual( self.bll.getIndexOf('A'), 2)
        self.assertEqual( self.bll.getIndexOf('B'), 1)
        self.assertEqual( self.bll.getIndexOf('C'), 0)
        self.assertEqual( self.bll.getIndexOf('D'), -1)
     
        
    def testOccurrencesOf(self):
        self.assertEqual( self.bll.occurrencesOf('A'), 1)
        self.assertEqual( self.bll.occurrencesOf('D'), 0)
        self.bll.insert('A')
        self.assertEqual( self.bll.occurrencesOf('A'), 2)
        
        
           
                              